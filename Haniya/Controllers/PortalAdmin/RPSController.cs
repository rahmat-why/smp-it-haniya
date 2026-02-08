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
    public class RPSController : Controller
    {
        private readonly IConfiguration _config;
        public RPSController(IConfiguration config) => _config = config;

        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        [HttpGet]
        public IActionResult Index() => View("~/Views/PortalAdmin/RPS/Index.cshtml");

        [HttpGet]
        public IActionResult Create() => View("~/Views/PortalAdmin/RPS/Create.cshtml");

        [HttpGet]
        public IActionResult Edit(string id)
        {
            ViewBag.rpsId = id;
            return View("~/Views/PortalAdmin/RPS/Edit.cshtml");
        }

        public IActionResult GetAll(string subject_id = null, string academic_year_id = null, string teacher_id = null, string class_id = null)
        {
            var (draw, start, length, _, _, _) = ParseDataTablesQuery();

            using var conn = GetConn();
            conn.Open();

            var totalSql = @"
                SELECT COUNT(*) 
                FROM mst_rps r
                WHERE r.status = 'ACTIVE'
                  AND (@subjectId IS NULL OR r.subject_id = @subjectId)
                  AND (@academicYearId IS NULL OR r.academic_year_id = @academicYearId)
                  AND (@teacherId IS NULL OR r.teacher_id = @teacherId)
                  AND (@classId IS NULL OR r.class_id = @classId)";

            int recordsTotal;
            using (var cmd = new SqlCommand(totalSql, conn))
            {
                cmd.Parameters.AddWithValue("@subjectId", (object)subject_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@academicYearId", (object)academic_year_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@teacherId", (object)teacher_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@classId", (object)class_id ?? DBNull.Value);
                recordsTotal = (int)cmd.ExecuteScalar();
            }

            var sql = @"
                SELECT 
                    r.rps_id,
                    s.subject_name,
                    CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                    c.class_name,
                    YEAR(ay.start_date) AS start_year,
                    YEAR(ay.end_date) AS end_year,
                    ay.semester,
                    COUNT(d.rps_detail_id) AS meeting_count
                FROM mst_rps r
                JOIN mst_subjects s ON r.subject_id = s.subject_id
                JOIN mst_teachers t ON r.teacher_id = t.teacher_id
                JOIN mst_classes c ON r.class_id = c.class_id
                JOIN mst_academic_years ay ON r.academic_year_id = ay.academic_year_id
                LEFT JOIN mst_rps_details d ON d.rps_id = r.rps_id
                WHERE r.status = 'ACTIVE'
                  AND (@subjectId IS NULL OR r.subject_id = @subjectId)
                  AND (@academicYearId IS NULL OR r.academic_year_id = @academicYearId)
                  AND (@teacherId IS NULL OR r.teacher_id = @teacherId)
                  AND (@classId IS NULL OR r.class_id = @classId)
                GROUP BY r.rps_id, s.subject_name, t.first_name, t.last_name, c.class_name,
                         ay.start_date, ay.end_date, ay.semester
                ORDER BY ay.start_date DESC, c.class_name, s.subject_name
                OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

            var list = new List<object>();
            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@subjectId", (object)subject_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@academicYearId", (object)academic_year_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@teacherId", (object)teacher_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@classId", (object)class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);

                using var r = cmd.ExecuteReader();
                while (r.Read())
                {
                    list.Add(new
                    {
                        rps_id = r["rps_id"].ToString(),
                        subject_name = r["subject_name"].ToString(),
                        teacher_name = r["teacher_name"].ToString(),
                        class_name = r["class_name"].ToString(),
                        academic_year = $"{r["start_year"]}/{r["end_year"]} - Sem {r["semester"]}",
                        meeting_count = Convert.ToInt32(r["meeting_count"])
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
                        r.rps_id,
                        r.subject_id,
                        s.subject_name,
                        r.class_id,
                        c.class_name,
                        r.academic_year_id,
                        YEAR(ay.start_date) AS start_year,
                        YEAR(ay.end_date) AS end_year,
                        ay.semester,
                        r.teacher_id,
                        CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                        r.description,
                        r.weight_attendance,
                        r.weight_task,
                        r.weight_uh,
                        r.weight_pts,
                        r.weight_pas
                    FROM mst_rps r
                    JOIN mst_subjects s ON r.subject_id = s.subject_id
                    JOIN mst_classes c ON r.class_id = c.class_id
                    JOIN mst_academic_years ay ON r.academic_year_id = ay.academic_year_id
                    JOIN mst_teachers t ON r.teacher_id = t.teacher_id
                    WHERE r.rps_id = @id";

                dynamic header = null;
                using (var cmd = new SqlCommand(headerSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    if (r.Read())
                    {
                        header = new
                        {
                            rps_id = r["rps_id"].ToString(),
                            subject_id = r["subject_id"]?.ToString(),
                            subject_name = r["subject_name"]?.ToString(),
                            class_id = r["class_id"]?.ToString(),
                            class_name = r["class_name"]?.ToString(),
                            academic_year_id = r["academic_year_id"]?.ToString(),
                            academic_year_display = $"{r["start_year"]}/{r["end_year"]} - Sem {r["semester"]}",
                            teacher_id = r["teacher_id"]?.ToString(),
                            teacher_name = r["teacher_name"]?.ToString(),
                            description = r["description"]?.ToString(),
                            weight_attendance = Convert.ToDecimal(r["weight_attendance"]),
                            weight_task = Convert.ToDecimal(r["weight_task"]),
                            weight_uh = Convert.ToDecimal(r["weight_uh"]),
                            weight_pts = Convert.ToDecimal(r["weight_pts"]),
                            weight_pas = Convert.ToDecimal(r["weight_pas"])
                        };
                    }
                    r.Close();
                }

                if (header == null) return Json(DTOResponse.fail("Not found", 404));

                var details = new List<object>();
                var detailsSql = @"
                    SELECT 
                        d.rps_detail_id,
                        d.meeting_number,
                        d.topic,
                        d.activity,
                        ds.item_desc AS activity_desc
                    FROM mst_rps_details d
                    LEFT JOIN mst_detail_settings ds ON d.activity = ds.detail_id AND ds.header_id = 'RPS_ACTIVITY'
                    WHERE d.rps_id = @id
                    ORDER BY d.meeting_number";

                using (var cmd = new SqlCommand(detailsSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    while (r.Read())
                    {
                        details.Add(new
                        {
                            rps_detail_id = r["rps_detail_id"]?.ToString(),
                            meeting_number = Convert.ToInt32(r["meeting_number"]),
                            topic = r["topic"]?.ToString(),
                            activity = r["activity"]?.ToString(),
                            activity_desc = r["activity_desc"]?.ToString()
                        });
                    }
                }

                return Json(DTOResponse.ok(new
                {
                    header.rps_id,
                    header.subject_id,
                    header.subject_name,
                    header.class_id,
                    header.class_name,
                    header.academic_year_id,
                    header.academic_year_display,
                    header.teacher_id,
                    header.teacher_name,
                    header.description,
                    header.weight_attendance,
                    header.weight_task,
                    header.weight_uh,
                    header.weight_pts,
                    header.weight_pas,
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
                var subjectId = f["subject_id"].ToString();
                var classId = f["class_id"].ToString();
                var academicYearId = f["academic_year_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var description = f["description"].ToString();
                var weightAttendance = f["weight_attendance"].ToString();
                var weightTask = f["weight_task"].ToString();
                var weightUh = f["weight_uh"].ToString();
                var weightPts = f["weight_pts"].ToString();
                var weightPas = f["weight_pas"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(subjectId) || string.IsNullOrWhiteSpace(classId) ||
                    string.IsNullOrWhiteSpace(academicYearId) || string.IsNullOrWhiteSpace(teacherId))
                    return Json(DTOResponse.fail("Subject, Class, Academic Year, and Teacher are required", 400));

                // Validate weights total = 100
                decimal totalWeight = 0;
                if (!string.IsNullOrWhiteSpace(weightAttendance)) totalWeight += decimal.Parse(weightAttendance);
                if (!string.IsNullOrWhiteSpace(weightTask)) totalWeight += decimal.Parse(weightTask);
                if (!string.IsNullOrWhiteSpace(weightUh)) totalWeight += decimal.Parse(weightUh);
                if (!string.IsNullOrWhiteSpace(weightPts)) totalWeight += decimal.Parse(weightPts);
                if (!string.IsNullOrWhiteSpace(weightPas)) totalWeight += decimal.Parse(weightPas);

                if (totalWeight != 100)
                    return Json(DTOResponse.fail("Total weight must equal 100%", 400));

                List<dynamic> details;
                using var json = JsonDocument.Parse(rawDetails);
                details = json.RootElement.EnumerateArray()
                    .Select(x => new
                    {
                        meeting_number = x.GetProperty("meeting_number").GetInt32(),
                        topic = x.GetProperty("topic").GetString(),
                        activity = x.GetProperty("activity").GetString()
                    })
                    .Where(d => !string.IsNullOrWhiteSpace(d.topic) && !string.IsNullOrWhiteSpace(d.activity))
                    .Cast<dynamic>()
                    .ToList();

                if (details.Count == 0)
                    return Json(DTOResponse.fail("At least one meeting detail is required", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                var seqCmd = new SqlCommand("SELECT ISNULL(MAX(rps_id),'RPS0000') FROM mst_rps", conn, trx);
                var seq = int.Parse(seqCmd.ExecuteScalar().ToString().Substring(3)) + 1;
                var rpsId = "RPS" + seq.ToString("D4");

                var headerSql = @"
                    INSERT INTO mst_rps (
                        rps_id, subject_id, class_id, academic_year_id, teacher_id, description,
                        weight_attendance, weight_task, weight_uh, weight_pts, weight_pas,
                        status, created_at
                    )
                    VALUES (
                        @id, @subjectId, @classId, @academicYearId, @teacherId, @description,
                        @weightAttendance, @weightTask, @weightUh, @weightPts, @weightPas,
                        'ACTIVE', GETDATE()
                    )";

                using var cmd = new SqlCommand(headerSql, conn, trx);
                cmd.Parameters.AddWithValue("@id", rpsId);
                cmd.Parameters.AddWithValue("@subjectId", subjectId);
                cmd.Parameters.AddWithValue("@classId", classId);
                cmd.Parameters.AddWithValue("@academicYearId", academicYearId);
                cmd.Parameters.AddWithValue("@teacherId", teacherId);
                cmd.Parameters.AddWithValue("@description", string.IsNullOrWhiteSpace(description) ? "" : description);
                cmd.Parameters.AddWithValue("@weightAttendance", string.IsNullOrWhiteSpace(weightAttendance) ? 0 : decimal.Parse(weightAttendance));
                cmd.Parameters.AddWithValue("@weightTask", string.IsNullOrWhiteSpace(weightTask) ? 0 : decimal.Parse(weightTask));
                cmd.Parameters.AddWithValue("@weightUh", string.IsNullOrWhiteSpace(weightUh) ? 0 : decimal.Parse(weightUh));
                cmd.Parameters.AddWithValue("@weightPts", string.IsNullOrWhiteSpace(weightPts) ? 0 : decimal.Parse(weightPts));
                cmd.Parameters.AddWithValue("@weightPas", string.IsNullOrWhiteSpace(weightPas) ? 0 : decimal.Parse(weightPas));
                cmd.ExecuteNonQuery();

                var detSeqCmd = new SqlCommand("SELECT ISNULL(MAX(rps_detail_id),'RPSD0000') FROM mst_rps_details", conn, trx);
                var detSeq = int.Parse(detSeqCmd.ExecuteScalar().ToString().Substring(4));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "RPSD" + detSeq.ToString("D4");

                    var detSql = @"
                        INSERT INTO mst_rps_details (
                            rps_detail_id, rps_id, meeting_number, topic, activity, created_at
                        )
                        VALUES (@did, @rid, @meetingNum, @topic, @activity, GETDATE())";

                    using var dcmd = new SqlCommand(detSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@rid", rpsId);
                    dcmd.Parameters.AddWithValue("@meetingNum", d.meeting_number);
                    dcmd.Parameters.AddWithValue("@topic", d.topic);
                    dcmd.Parameters.AddWithValue("@activity", d.activity);
                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "RPS created successfully"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpPost]
        public IActionResult Update()
        {
            try
            {
                var f = Request.Form;
                var rpsId = f["rps_id"].ToString();
                var subjectId = f["subject_id"].ToString();
                var classId = f["class_id"].ToString();
                var academicYearId = f["academic_year_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var description = f["description"].ToString();
                var weightAttendance = f["weight_attendance"].ToString();
                var weightTask = f["weight_task"].ToString();
                var weightUh = f["weight_uh"].ToString();
                var weightPts = f["weight_pts"].ToString();
                var weightPas = f["weight_pas"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(rpsId))
                    return Json(DTOResponse.fail("Invalid RPS ID", 400));

                if (string.IsNullOrWhiteSpace(subjectId) || string.IsNullOrWhiteSpace(classId) ||
                    string.IsNullOrWhiteSpace(academicYearId) || string.IsNullOrWhiteSpace(teacherId))
                    return Json(DTOResponse.fail("Subject, Class, Academic Year, and Teacher are required", 400));

                // Validate weights total = 100
                decimal totalWeight = 0;
                if (!string.IsNullOrWhiteSpace(weightAttendance)) totalWeight += decimal.Parse(weightAttendance);
                if (!string.IsNullOrWhiteSpace(weightTask)) totalWeight += decimal.Parse(weightTask);
                if (!string.IsNullOrWhiteSpace(weightUh)) totalWeight += decimal.Parse(weightUh);
                if (!string.IsNullOrWhiteSpace(weightPts)) totalWeight += decimal.Parse(weightPts);
                if (!string.IsNullOrWhiteSpace(weightPas)) totalWeight += decimal.Parse(weightPas);

                if (totalWeight != 100)
                    return Json(DTOResponse.fail("Total weight must equal 100%", 400));

                List<dynamic> details;
                using var json = JsonDocument.Parse(rawDetails);
                details = json.RootElement.EnumerateArray()
                    .Select(x => new
                    {
                        meeting_number = x.GetProperty("meeting_number").GetInt32(),
                        topic = x.GetProperty("topic").GetString(),
                        activity = x.GetProperty("activity").GetString()
                    })
                    .Where(d => !string.IsNullOrWhiteSpace(d.topic) && !string.IsNullOrWhiteSpace(d.activity))
                    .Cast<dynamic>()
                    .ToList();

                if (details.Count == 0)
                    return Json(DTOResponse.fail("At least one meeting detail is required", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                var headerSql = @"
                    UPDATE mst_rps 
                    SET subject_id = @subjectId, 
                        class_id = @classId,
                        academic_year_id = @academicYearId, 
                        teacher_id = @teacherId, 
                        description = @description,
                        weight_attendance = @weightAttendance, 
                        weight_task = @weightTask, 
                        weight_uh = @weightUh, 
                        weight_pts = @weightPts, 
                        weight_pas = @weightPas,
                        updated_at = GETDATE()
                    WHERE rps_id = @id";

                using var cmd = new SqlCommand(headerSql, conn, trx);
                cmd.Parameters.AddWithValue("@id", rpsId);
                cmd.Parameters.AddWithValue("@subjectId", subjectId);
                cmd.Parameters.AddWithValue("@classId", classId);
                cmd.Parameters.AddWithValue("@academicYearId", academicYearId);
                cmd.Parameters.AddWithValue("@teacherId", teacherId);
                cmd.Parameters.AddWithValue("@description", string.IsNullOrWhiteSpace(description) ? "" : description);
                cmd.Parameters.AddWithValue("@weightAttendance", string.IsNullOrWhiteSpace(weightAttendance) ? 0 : decimal.Parse(weightAttendance));
                cmd.Parameters.AddWithValue("@weightTask", string.IsNullOrWhiteSpace(weightTask) ? 0 : decimal.Parse(weightTask));
                cmd.Parameters.AddWithValue("@weightUh", string.IsNullOrWhiteSpace(weightUh) ? 0 : decimal.Parse(weightUh));
                cmd.Parameters.AddWithValue("@weightPts", string.IsNullOrWhiteSpace(weightPts) ? 0 : decimal.Parse(weightPts));
                cmd.Parameters.AddWithValue("@weightPas", string.IsNullOrWhiteSpace(weightPas) ? 0 : decimal.Parse(weightPas));
                cmd.ExecuteNonQuery();

                new SqlCommand("DELETE FROM mst_rps_details WHERE rps_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", rpsId) }
                }.ExecuteNonQuery();

                var detSeqCmd = new SqlCommand("SELECT ISNULL(MAX(rps_detail_id),'RPSD0000') FROM mst_rps_details", conn, trx);
                var detSeq = int.Parse(detSeqCmd.ExecuteScalar().ToString().Substring(4));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "RPSD" + detSeq.ToString("D4");

                    var detSql = @"
                        INSERT INTO mst_rps_details (
                            rps_detail_id, rps_id, meeting_number, topic, activity, created_at
                        )
                        VALUES (@did, @rid, @meetingNum, @topic, @activity, GETDATE())";

                    using var dcmd = new SqlCommand(detSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@rid", rpsId);
                    dcmd.Parameters.AddWithValue("@meetingNum", d.meeting_number);
                    dcmd.Parameters.AddWithValue("@topic", d.topic);
                    dcmd.Parameters.AddWithValue("@activity", d.activity);
                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "RPS updated successfully"));
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

                new SqlCommand("DELETE FROM mst_rps_details WHERE rps_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", req.id) }
                }.ExecuteNonQuery();

                new SqlCommand("DELETE FROM mst_rps WHERE rps_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", req.id) }
                }.ExecuteNonQuery();

                trx.Commit();
                return Json(DTOResponse.ok(null, "RPS deleted"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }
    }
}