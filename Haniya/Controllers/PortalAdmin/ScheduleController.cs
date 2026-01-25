using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.IO;
using System.Linq;
using System.Text.Json;
using Haniya.Models;

namespace Haniya.Controllers.PortalAdmin
{
    public class ScheduleController : Controller
    {
        private readonly IConfiguration _config;

        public ScheduleController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        // ========= MASTER LOADERS =========

        private List<dynamic> GetClassOptions()
        {
            var list = new List<dynamic>();

            using var conn = GetConn();
            conn.Open();

            var sql = @"
        SELECT 
            ac.academic_class_id,
            c.class_name
        FROM mst_academic_classes ac
        JOIN mst_classes c ON ac.class_id = c.class_id
        ORDER BY c.class_name";

            using var cmd = new SqlCommand(sql, conn);
            using var rd = cmd.ExecuteReader();

            while (rd.Read())
            {
                list.Add(new
                {
                    Id = rd["academic_class_id"]?.ToString(),
                    Name = rd["class_name"]?.ToString()
                });
            }

            return list;
        }

        private List<dynamic> GetSubjectOptions()
        {
            var list = new List<dynamic>();

            using var conn = GetConn();
            conn.Open();

            var sql = @"
        SELECT subject_id, subject_name
        FROM mst_subjects
        ORDER BY subject_name";

            using var cmd = new SqlCommand(sql, conn);
            using var rd = cmd.ExecuteReader();

            while (rd.Read())
            {
                list.Add(new
                {
                    Id = rd["subject_id"]?.ToString(),
                    Name = rd["subject_name"]?.ToString()
                });
            }

            return list;
        }

        private List<dynamic> GetTeacherOptions()
        {
            var list = new List<dynamic>();

            using var conn = GetConn();
            conn.Open();

            var sql = @"
        SELECT 
            teacher_id,
            CONCAT(first_name, ' ', last_name) AS teacher_name
        FROM mst_teachers
        WHERE status = 'ACTIVE'
        ORDER BY first_name";

            using var cmd = new SqlCommand(sql, conn);
            using var rd = cmd.ExecuteReader();

            while (rd.Read())
            {
                list.Add(new
                {
                    Id = rd["teacher_id"]?.ToString(),
                    Name = rd["teacher_name"]?.ToString()
                });
            }

            return list;
        }

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Schedule/Index.cshtml");
        }

        public IActionResult Create()
        {
            ViewBag.ClassOptions = GetClassOptions();
            ViewBag.SubjectOptions = GetSubjectOptions();
            ViewBag.TeacherOptions = GetTeacherOptions();
            return View("~/Views/PortalAdmin/Schedule/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.scheduleId = id;
            ViewBag.ClassOptions = GetClassOptions();
            ViewBag.SubjectOptions = GetSubjectOptions();
            ViewBag.TeacherOptions = GetTeacherOptions();
            return View("~/Views/PortalAdmin/Schedule/Edit.cshtml");
        }

        // ========= API =========
        [HttpGet]
        public IActionResult GetAll()
        {
            try
            {
                var list = new List<object>();

                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT
                s.schedule_id,
                s.day,
                c.class_name,
                COUNT(d.schedule_detail_id) AS detail_count
            FROM txn_schedules s
            LEFT JOIN mst_academic_classes ac
                ON s.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c
                ON ac.class_id = c.class_id
            LEFT JOIN txn_schedule_details d
                ON s.schedule_id = d.schedule_id
            GROUP BY
                s.schedule_id,
                s.day,
                c.class_name
            ORDER BY
                c.class_name,
                s.day";

                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();

                while (rd.Read())
                {
                    list.Add(new
                    {
                        schedule_id = rd["schedule_id"]?.ToString(),
                        day = rd["day"]?.ToString(),
                        class_name = rd["class_name"]?.ToString(),
                        detail_count = Convert.ToInt32(rd["detail_count"])
                    });
                }

                return Json(DTOResponse.ok(list));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetById(string id)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(id))
                    return Json(DTOResponse.fail("invalid schedule id", 400));

                using var conn = GetConn();
                conn.Open();

                // ===== get schedule header =====
                var sql = @"
            SELECT
                s.schedule_id,
                s.day,
                s.academic_class_id,
                c.class_name
            FROM txn_schedules s
            LEFT JOIN mst_academic_classes ac
                ON s.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c
                ON ac.class_id = c.class_id
            WHERE s.schedule_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                var schedule = new
                {
                    schedule_id = rd["schedule_id"]?.ToString(),
                    day = rd["day"]?.ToString(),
                    academic_class_id = rd["academic_class_id"]?.ToString(),
                    class_name = rd["class_name"]?.ToString()
                };

                rd.Close();

                // ===== get schedule details =====
                var details = new List<object>();

                var dsql = @"
            SELECT
                d.schedule_detail_id,
                d.subject_id,
                d.teacher_id,
                CONVERT(VARCHAR(5), d.start_time, 108) AS start_time,
                CONVERT(VARCHAR(5), d.end_time, 108) AS end_time,
                s.subject_name,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
            FROM txn_schedule_details d
            LEFT JOIN mst_subjects s
                ON d.subject_id = s.subject_id
            LEFT JOIN mst_teachers t
                ON d.teacher_id = t.teacher_id
            WHERE d.schedule_id = @id
            ORDER BY d.start_time";

                using var dcmd = new SqlCommand(dsql, conn);
                dcmd.Parameters.AddWithValue("@id", id);

                using var drd = dcmd.ExecuteReader();
                while (drd.Read())
                {
                    details.Add(new
                    {
                        schedule_detail_id = drd["schedule_detail_id"]?.ToString(),
                        subject_id = drd["subject_id"]?.ToString(),
                        teacher_id = drd["teacher_id"]?.ToString(),
                        start_time = drd["start_time"]?.ToString(),
                        end_time = drd["end_time"]?.ToString(),
                        subject_name = drd["subject_name"]?.ToString(),
                        teacher_name = drd["teacher_name"]?.ToString()
                    });
                }

                return Json(DTOResponse.ok(new
                {
                    schedule.schedule_id,
                    schedule.day,
                    schedule.academic_class_id,
                    schedule.class_name,
                    details
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Create(DTORequest req)
        {
            try
            {
                var f = Request.Form;

                var day = f["day"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(day))
                    return Json(DTOResponse.fail("day is required", 400));

                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("class is required", 400));

                if (string.IsNullOrWhiteSpace(rawDetails))
                    return Json(DTOResponse.fail("schedule details are required", 400));

                // ===== parse details (NO DTO) =====
                List<dynamic> details;
                try
                {
                    using var json = JsonDocument.Parse(rawDetails);
                    details = json.RootElement
                        .EnumerateArray()
                        .Select(x => new
                        {
                            subject_id = x.GetProperty("subject_id").GetString(),
                            teacher_id = x.GetProperty("teacher_id").GetString(),
                            start_time = x.GetProperty("start_time").GetString(),
                            end_time = x.GetProperty("end_time").GetString()
                        })
                        .Where(d =>
                            !string.IsNullOrWhiteSpace(d.subject_id) &&
                            !string.IsNullOrWhiteSpace(d.teacher_id) &&
                            !string.IsNullOrWhiteSpace(d.start_time) &&
                            !string.IsNullOrWhiteSpace(d.end_time)
                        )
                        .Cast<dynamic>()
                        .ToList();
                }
                catch
                {
                    return Json(DTOResponse.fail("invalid details format", 400));
                }

                if (details.Count == 0)
                    return Json(DTOResponse.fail("at least one detail row is required", 400));

                using var conn = GetConn();
                conn.Open();

                // ===== generate schedule_id =====
                var lastIdCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(schedule_id),'SCH0000') FROM txn_schedules",
                    conn
                );
                var lastId = lastIdCmd.ExecuteScalar().ToString();
                var next = int.Parse(lastId.Substring(3)) + 1;
                var scheduleId = "SCH" + next.ToString("D4");

                // ===== insert schedule =====
                var sql = @"
            INSERT INTO txn_schedules (
                schedule_id,
                day,
                academic_class_id,
                created_at
            ) VALUES (
                @id,
                @day,
                @cls,
                GETDATE()
            )";

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", scheduleId);
                    cmd.Parameters.AddWithValue("@day", day);
                    cmd.Parameters.AddWithValue("@cls", academicClassId);
                    cmd.ExecuteNonQuery();
                }

                // ===== generate detail id seed =====
                var lastDetCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(schedule_detail_id),'SCD0000') FROM txn_schedule_details",
                    conn
                );
                var lastDetId = lastDetCmd.ExecuteScalar().ToString();
                var detSeq = int.Parse(lastDetId.Substring(3));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "SCD" + detSeq.ToString("D4");

                    var dsql = @"
                INSERT INTO txn_schedule_details (
                    schedule_detail_id,
                    schedule_id,
                    subject_id,
                    teacher_id,
                    start_time,
                    end_time,
                    created_at
                ) VALUES (
                    @did,
                    @sid,
                    @sub,
                    @tch,
                    @st,
                    @et,
                    GETDATE()
                )";

                    using var dcmd = new SqlCommand(dsql, conn);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@sid", scheduleId);
                    dcmd.Parameters.AddWithValue("@sub", d.subject_id);
                    dcmd.Parameters.AddWithValue("@tch", d.teacher_id);
                    dcmd.Parameters.AddWithValue("@st", TimeSpan.Parse(d.start_time));
                    dcmd.Parameters.AddWithValue("@et", TimeSpan.Parse(d.end_time));
                    dcmd.ExecuteNonQuery();
                }

                return Json(DTOResponse.ok(null, "schedule created"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Update(DTORequest req)
        {
            try
            {
                var f = Request.Form;

                var scheduleId = f["schedule_id"].ToString();
                var day = f["day"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(scheduleId))
                    return Json(DTOResponse.fail("invalid schedule id", 400));

                if (string.IsNullOrWhiteSpace(day))
                    return Json(DTOResponse.fail("day is required", 400));

                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("class is required", 400));

                if (string.IsNullOrWhiteSpace(rawDetails))
                    return Json(DTOResponse.fail("schedule details are required", 400));

                // ===== parse details (NO DTO) =====
                List<dynamic> details;
                try
                {
                    using var json = JsonDocument.Parse(rawDetails);
                    details = json.RootElement
                        .EnumerateArray()
                        .Select(x => new
                        {
                            subject_id = x.GetProperty("subject_id").GetString(),
                            teacher_id = x.GetProperty("teacher_id").GetString(),
                            start_time = x.GetProperty("start_time").GetString(),
                            end_time = x.GetProperty("end_time").GetString()
                        })
                        .Where(d =>
                            !string.IsNullOrWhiteSpace(d.subject_id) &&
                            !string.IsNullOrWhiteSpace(d.teacher_id) &&
                            !string.IsNullOrWhiteSpace(d.start_time) &&
                            !string.IsNullOrWhiteSpace(d.end_time)
                        )
                        .Cast<dynamic>()
                        .ToList();
                }
                catch
                {
                    return Json(DTOResponse.fail("invalid details format", 400));
                }

                if (details.Count == 0)
                    return Json(DTOResponse.fail("at least one detail row is required", 400));

                using var conn = GetConn();
                conn.Open();

                // ===== update schedule =====
                var sql = @"
            UPDATE txn_schedules SET
                day = @day,
                academic_class_id = @cls,
                updated_at = GETDATE()
            WHERE schedule_id = @id";

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", scheduleId);
                    cmd.Parameters.AddWithValue("@day", day);
                    cmd.Parameters.AddWithValue("@cls", academicClassId);
                    cmd.ExecuteNonQuery();
                }

                // ===== delete old details =====
                using (var del = new SqlCommand(
                    "DELETE FROM txn_schedule_details WHERE schedule_id=@id",
                    conn))
                {
                    del.Parameters.AddWithValue("@id", scheduleId);
                    del.ExecuteNonQuery();
                }

                // ===== generate new detail ids =====
                var lastDetCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(schedule_detail_id),'SCD0000') FROM txn_schedule_details",
                    conn
                );
                var lastDetId = lastDetCmd.ExecuteScalar().ToString();
                var detSeq = int.Parse(lastDetId.Substring(3));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "SCD" + detSeq.ToString("D4");

                    var dsql = @"
                INSERT INTO txn_schedule_details (
                    schedule_detail_id,
                    schedule_id,
                    subject_id,
                    teacher_id,
                    start_time,
                    end_time,
                    created_at
                ) VALUES (
                    @did,
                    @sid,
                    @sub,
                    @tch,
                    @st,
                    @et,
                    GETDATE()
                )";

                    using var dcmd = new SqlCommand(dsql, conn);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@sid", scheduleId);
                    dcmd.Parameters.AddWithValue("@sub", d.subject_id);
                    dcmd.Parameters.AddWithValue("@tch", d.teacher_id);
                    dcmd.Parameters.AddWithValue("@st", TimeSpan.Parse(d.start_time));
                    dcmd.Parameters.AddWithValue("@et", TimeSpan.Parse(d.end_time));
                    dcmd.ExecuteNonQuery();
                }

                return Json(DTOResponse.ok(null, "schedule updated"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Delete([FromBody] DTORequest req)
        {
            try
            {
                if (string.IsNullOrEmpty(req?.id))
                    return Json(DTOResponse.fail("invalid schedule id", 400));

                using var conn = GetConn();
                conn.Open();

                // delete details first (no ON DELETE CASCADE in DDL)
                var delDet = new SqlCommand(
                    "DELETE FROM txn_schedule_details WHERE schedule_id=@id",
                    conn
                );
                delDet.Parameters.AddWithValue("@id", req.id);
                delDet.ExecuteNonQuery();

                var cmd = new SqlCommand(
                    "DELETE FROM txn_schedules WHERE schedule_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "schedule deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}