using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Text.Json;
using Haniya.Models;
using System.Data;
using System.Linq;

namespace Haniya.Controllers.PortalAdmin
{
    public class ScheduleController : Controller
    {
        private readonly IConfiguration _config;
        public ScheduleController(IConfiguration config) => _config = config;

        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        [HttpGet]
        public IActionResult Index() => View("~/Views/PortalAdmin/Schedule/Index.cshtml");

        [HttpGet]
        public IActionResult Create() => View("~/Views/PortalAdmin/Schedule/Create.cshtml");

        [HttpGet]
        public IActionResult Edit(string id)
        {
            ViewBag.scheduleId = id;
            return View("~/Views/PortalAdmin/Schedule/Edit.cshtml");
        }

        public IActionResult GetAll(string academic_class_id = null, string day = null)
        {
            var (draw, start, length, _, _, _) = ParseDataTablesQuery();

            using var conn = GetConn();
            conn.Open();

            var totalSql = @"
                SELECT COUNT(*) 
                FROM mst_schedules s
                JOIN mst_detail_settings mds ON (s.day = mds.item_name OR s.day = mds.detail_id) AND mds.header_id = 'DAY'
                JOIN mst_academic_classes ac ON s.academic_class_id = ac.academic_class_id
                WHERE (@classId IS NULL OR s.academic_class_id = @classId)
                  AND (
                        @day IS NULL
                        OR s.day = @day
                        OR mds.item_name = @day
                        OR mds.item_desc = @day
                        OR mds.detail_id = @day
                  )";

            int recordsTotal;
            using (var cmd = new SqlCommand(totalSql, conn))
            {
                cmd.Parameters.AddWithValue("@classId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@day", (object)day ?? DBNull.Value);
                recordsTotal = (int)cmd.ExecuteScalar();
            }

            var sql = @"
                SELECT 
                    s.schedule_id,
                    c.class_name,
                    mds.item_name AS day,
                    COUNT(d.schedule_detail_id) AS lesson_count
                FROM mst_schedules s
                JOIN mst_academic_classes ac ON s.academic_class_id = ac.academic_class_id
                JOIN mst_classes c ON ac.class_id = c.class_id
                JOIN mst_detail_settings mds ON (s.day = mds.item_name OR s.day = mds.detail_id) AND mds.header_id = 'DAY'
                LEFT JOIN mst_schedule_details d ON d.schedule_id = s.schedule_id
                WHERE (@classId IS NULL OR s.academic_class_id = @classId)
                  AND (
                        @day IS NULL
                        OR s.day = @day
                        OR mds.item_name = @day
                        OR mds.item_desc = @day
                        OR mds.detail_id = @day
                  )
                GROUP BY s.schedule_id, c.class_name, mds.item_name
                ORDER BY c.class_name, mds.item_name
                OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

            var list = new List<object>();
            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@classId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@day", (object)day ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);

                using var r = cmd.ExecuteReader();
                while (r.Read())
                {
                    list.Add(new
                    {
                        schedule_id = r["schedule_id"].ToString(),
                        class_name = r["class_name"].ToString(),
                        day = r["day"].ToString(),
                        lesson_count = Convert.ToInt32(r["lesson_count"])
                    });
                }
            }

            return Json(new { draw, recordsTotal, recordsFiltered = recordsTotal, data = list });
        }

        private (int draw, int start, int length, string searchValue, int orderColumnIndex, string orderDir) ParseDataTablesQuery()
        {
            var q = Request.Query;
            int.TryParse(q["draw"], out var draw); draw = draw > 0 ? draw : 1;
            int.TryParse(q["start"], out var start);
            int.TryParse(q["length"], out var length); length = length > 0 ? length : 10;
            var searchValue = q["search[value]"].ToString() ?? "";
            int.TryParse(q["order[0][column]"], out var orderColumnIndex);
            var orderDir = q["order[0][dir]"].ToString().ToUpper() is "ASC" or "DESC" ? q["order[0][dir]"].ToString().ToUpper() : "ASC";
            return (draw, start, length, searchValue, orderColumnIndex, orderDir);
        }

        [HttpGet]
        public IActionResult GetById(string id)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(id)) return Json(DTOResponse.fail("Invalid ID", 400));

                using var conn = GetConn();
                conn.Open();

                var headerSql = @"
            SELECT 
                s.schedule_id,
                s.day,
                s.academic_class_id,
                c.class_name,
                COALESCE(dt.item_name, s.day) AS day_value,
                COALESCE(dt.item_desc, dt.item_name, s.day) AS day_desc
            FROM mst_schedules s
            JOIN mst_academic_classes ac ON s.academic_class_id = ac.academic_class_id
            JOIN mst_classes c ON ac.class_id = c.class_id
            LEFT JOIN mst_detail_settings dt ON (s.day = dt.detail_id OR s.day = dt.item_name) AND dt.header_id = 'DAY'
            WHERE s.schedule_id = @id";

                dynamic header = null;
                using (var cmd = new SqlCommand(headerSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    if (r.Read())
                    {
                        header = new
                        {
                            schedule_id = r["schedule_id"].ToString(),
                            day = r["day_value"]?.ToString(),
                            day_desc = r["day_desc"]?.ToString(),
                            academic_class_id = r["academic_class_id"]?.ToString(),
                            class_name = r["class_name"]?.ToString()
                        };
                    }
                    r.Close();
                }

                if (header == null) return Json(DTOResponse.fail("Not found", 404));

                var details = new List<object>();
                var detailsSql = @"
            SELECT 
                d.schedule_detail_id,
                d.subject_id,
                CASE 
                    WHEN NULLIF(LTRIM(RTRIM(s.class_level)), '') IS NULL THEN s.subject_name
                    ELSE CONCAT(s.subject_name, ' - Class ', s.class_level)
                END AS subject_name,
                d.teacher_id,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                CONVERT(varchar(5), d.start_time, 108) AS start_time,
                CONVERT(varchar(5), d.end_time, 108) AS end_time
            FROM mst_schedule_details d
            LEFT JOIN mst_subjects s ON d.subject_id = s.subject_id
            LEFT JOIN mst_teachers t ON d.teacher_id = t.teacher_id
            WHERE d.schedule_id = @id
            ORDER BY d.start_time";

                using (var cmd = new SqlCommand(detailsSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    while (r.Read())
                    {
                        details.Add(new
                        {
                            schedule_detail_id = r["schedule_detail_id"]?.ToString(),
                            subject_id = r["subject_id"]?.ToString(),
                            subject_name = r["subject_name"]?.ToString(),
                            teacher_id = r["teacher_id"]?.ToString(),
                            teacher_name = r["teacher_name"]?.ToString(),
                            start_time = r["start_time"]?.ToString(),
                            end_time = r["end_time"]?.ToString()
                        });
                    }
                }

                return Json(DTOResponse.ok(new
                {
                    header.schedule_id,
                    header.day,
                    header.day_desc,
                    header.academic_class_id,
                    header.class_name,
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
                var academicClassId = f["academic_class_id"].ToString();
                var day = f["day"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(academicClassId) || string.IsNullOrWhiteSpace(day))
                    return Json(DTOResponse.fail("Class and day are required", 400));

                List<dynamic> details;
                using var json = JsonDocument.Parse(rawDetails);
                details = json.RootElement.EnumerateArray()
                    .Select(x => new
                    {
                        subject_id = x.GetProperty("subject_id").GetString(),
                        teacher_id = x.GetProperty("teacher_id").GetString(),
                        start_time = x.GetProperty("start_time").GetString(),
                        end_time = x.GetProperty("end_time").GetString()
                    })
                    .Where(d => !string.IsNullOrWhiteSpace(d.subject_id) && !string.IsNullOrWhiteSpace(d.teacher_id) &&
                                !string.IsNullOrWhiteSpace(d.start_time) && !string.IsNullOrWhiteSpace(d.end_time))
                    .Cast<dynamic>()
                    .ToList();

                if (details.Count == 0) return Json(DTOResponse.fail("At least one schedule detail is required", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                var seqCmd = new SqlCommand("SELECT ISNULL(MAX(schedule_id),'SCH0000') FROM mst_schedules", conn, trx);
                var seq = int.Parse(seqCmd.ExecuteScalar().ToString().Substring(3)) + 1;
                var scheduleId = "SCH" + seq.ToString("D4");

                var headerSql = @"
                    INSERT INTO mst_schedules (schedule_id, academic_class_id, day, created_at)
                    VALUES (@id, @classId, @day, GETDATE())";

                using var cmd = new SqlCommand(headerSql, conn, trx);
                cmd.Parameters.AddWithValue("@id", scheduleId);
                cmd.Parameters.AddWithValue("@classId", academicClassId);
                cmd.Parameters.AddWithValue("@day", day);
                cmd.ExecuteNonQuery();

                var detSeqCmd = new SqlCommand("SELECT ISNULL(MAX(schedule_detail_id),'SCD0000') FROM mst_schedule_details", conn, trx);
                var detSeq = int.Parse(detSeqCmd.ExecuteScalar().ToString().Substring(3));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "SCD" + detSeq.ToString("D4");

                    var detSql = @"
                        INSERT INTO mst_schedule_details (schedule_detail_id, schedule_id, subject_id, teacher_id, start_time, end_time, created_at)
                        VALUES (@did, @sid, @subj, @tch, @start, @end, GETDATE())";

                    using var dcmd = new SqlCommand(detSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@sid", scheduleId);
                    dcmd.Parameters.AddWithValue("@subj", d.subject_id);
                    dcmd.Parameters.AddWithValue("@tch", d.teacher_id);
                    dcmd.Parameters.AddWithValue("@start", d.start_time);
                    dcmd.Parameters.AddWithValue("@end", d.end_time);
                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "Schedule created successfully"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpPost]
        public IActionResult Update()
        {
            try
            {
                var f = Request.Form;
                var scheduleId = f["schedule_id"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var day = f["day"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(scheduleId)) return Json(DTOResponse.fail("Invalid schedule ID", 400));
                if (string.IsNullOrWhiteSpace(academicClassId) || string.IsNullOrWhiteSpace(day)) return Json(DTOResponse.fail("Class and day are required", 400));

                List<dynamic> details;
                using var json = JsonDocument.Parse(rawDetails);
                details = json.RootElement.EnumerateArray()
                    .Select(x => new
                    {
                        subject_id = x.GetProperty("subject_id").GetString(),
                        teacher_id = x.GetProperty("teacher_id").GetString(),
                        start_time = x.GetProperty("start_time").GetString(),
                        end_time = x.GetProperty("end_time").GetString()
                    })
                    .Where(d => !string.IsNullOrWhiteSpace(d.subject_id) && !string.IsNullOrWhiteSpace(d.teacher_id) &&
                                !string.IsNullOrWhiteSpace(d.start_time) && !string.IsNullOrWhiteSpace(d.end_time))
                    .Cast<dynamic>()
                    .ToList();

                if (details.Count == 0) return Json(DTOResponse.fail("At least one schedule detail is required", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                var headerSql = @"
                    UPDATE mst_schedules 
                    SET academic_class_id = @classId, day = @day, updated_at = GETDATE()
                    WHERE schedule_id = @id";

                using var cmd = new SqlCommand(headerSql, conn, trx);
                cmd.Parameters.AddWithValue("@id", scheduleId);
                cmd.Parameters.AddWithValue("@classId", academicClassId);
                cmd.Parameters.AddWithValue("@day", day);
                cmd.ExecuteNonQuery();

                new SqlCommand("DELETE FROM mst_schedule_details WHERE schedule_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", scheduleId) }
                }.ExecuteNonQuery();

                var detSeqCmd = new SqlCommand("SELECT ISNULL(MAX(schedule_detail_id),'SCD0000') FROM mst_schedule_details", conn, trx);
                var detSeq = int.Parse(detSeqCmd.ExecuteScalar().ToString().Substring(3));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "SCD" + detSeq.ToString("D4");

                    var detSql = @"
                        INSERT INTO mst_schedule_details (schedule_detail_id, schedule_id, subject_id, teacher_id, start_time, end_time, created_at)
                        VALUES (@did, @sid, @subj, @tch, @start, @end, GETDATE())";

                    using var dcmd = new SqlCommand(detSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@sid", scheduleId);
                    dcmd.Parameters.AddWithValue("@subj", d.subject_id);
                    dcmd.Parameters.AddWithValue("@tch", d.teacher_id);
                    dcmd.Parameters.AddWithValue("@start", d.start_time);
                    dcmd.Parameters.AddWithValue("@end", d.end_time);
                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "Schedule updated successfully"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpPost]
        public IActionResult Delete([FromBody] DTORequest req)
        {
            try
            {
                if (string.IsNullOrEmpty(req?.id)) return Json(DTOResponse.fail("Invalid ID", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                new SqlCommand("DELETE FROM mst_schedule_details WHERE schedule_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", req.id) }
                }.ExecuteNonQuery();

                new SqlCommand("DELETE FROM mst_schedules WHERE schedule_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", req.id) }
                }.ExecuteNonQuery();

                trx.Commit();
                return Json(DTOResponse.ok(null, "Schedule deleted"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }
    }
}
