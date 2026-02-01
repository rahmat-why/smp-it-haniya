using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Linq;
using System.Text.Json;
using Haniya.Models;
using System.Data;
using System.Globalization;

namespace Haniya.Controllers.PortalAdmin
{
    public class AttendanceController : Controller
    {
        private readonly IConfiguration _config;

        public AttendanceController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

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

        private List<dynamic> GetStudentOptions()
        {
            var list = new List<dynamic>();

            using var conn = GetConn();
            conn.Open();

            var sql = @"
        SELECT
            student_id,
            full_name
        FROM mst_students
        WHERE status = 'ACTIVE'
        ORDER BY full_name";

            using var cmd = new SqlCommand(sql, conn);
            using var rd = cmd.ExecuteReader();

            while (rd.Read())
            {
                list.Add(new
                {
                    Id = rd["student_id"]?.ToString(),
                    Name = rd["full_name"]?.ToString()
                });
            }

            return list;
        }

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Attendance/Index.cshtml");
        }

        public IActionResult Create()
        {
            return View("~/Views/PortalAdmin/Attendance/Create.cshtml");
        }

        [HttpGet]
        public IActionResult GetStudentsByClass(string academicClassId)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("Academic class ID is required", 400));

                var students = new List<object>();

                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT 
                s.student_id,
                s.full_name,
                s.nis,
                s.profile_photo
            FROM mst_student_classes sc
            JOIN mst_students s ON sc.student_id = s.student_id
            WHERE sc.academic_class_id = @academicClassId
              AND s.status = 'ACTIVE'
            ORDER BY s.full_name ASC";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@academicClassId", academicClassId);

                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    students.Add(new
                    {
                        id = rd["student_id"].ToString(),
                        full_name = rd["full_name"]?.ToString(),
                        nis = rd["nis"]?.ToString(),
                        profile_photo = rd["profile_photo"]?.ToString() ?? "/image/no-image.png"
                    });
                }

                return Json(DTOResponse.ok(students));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        // Get homeroom teacher for a specific academic class
        [HttpGet]
        public IActionResult GetTeacherByClass(string academicClassId)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("Academic class ID is required", 400));

                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT 
                t.teacher_id,
                CONCAT(t.first_name, ' ', t.last_name) AS full_name,
                t.npk
            FROM mst_academic_classes ac
            JOIN mst_teachers t ON ac.homeroom_teacher_id = t.teacher_id
            WHERE ac.academic_class_id = @academicClassId";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@academicClassId", academicClassId);

                using var rd = cmd.ExecuteReader();
                if (rd.Read())
                {
                    var id = rd["teacher_id"].ToString();
                    var name = rd["full_name"].ToString();
                    var npk = rd["npk"]?.ToString();
                    var text = $"{name} - {npk}";

                    return Json(DTOResponse.ok(new { id, text }));
                }

                return Json(DTOResponse.ok(null)); // No teacher found
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        public IActionResult Edit(string id)
        {
            ViewBag.attendanceId = id;
            return View("~/Views/PortalAdmin/Attendance/Edit.cshtml");
        }

        public IActionResult GetAll(string attendance_date = null)
        {
            var (draw, start, length, search, orderColumn, orderDir) = ParseDataTablesQuery();

            using var conn = GetConn();
            conn.Open();

            DateTime date = DateTime.Today;
            if (!string.IsNullOrWhiteSpace(attendance_date))
                DateTime.TryParse(attendance_date, out date);

            // TOTAL
            int recordsTotal;
            using (var cmd = new SqlCommand(
                "SELECT COUNT(*) FROM dbo.txn_attendances WHERE CAST(attendance_date AS DATE)=@date", conn))
            {
                cmd.Parameters.AddWithValue("@date", date);
                recordsTotal = (int)cmd.ExecuteScalar();
            }

            // DATA
            var sql = @"
    SELECT
        a.attendance_id,
        a.attendance_date,
        c.class_name,
        CONCAT(t.first_name,' ',t.last_name) AS teacher_name,
        SUM(CASE WHEN d.status='PRESENT' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN d.status='SICK' THEN 1 ELSE 0 END) AS sick,
        SUM(CASE WHEN d.status='EXCUSED' THEN 1 ELSE 0 END) AS permit,
        SUM(CASE WHEN d.status='NOINFO' THEN 1 ELSE 0 END) AS alpha
    FROM dbo.txn_attendances a
    JOIN dbo.mst_academic_classes ac ON ac.academic_class_id = a.academic_class_id
    JOIN dbo.mst_classes c ON c.class_id = ac.class_id
    JOIN dbo.mst_teachers t ON t.teacher_id = a.teacher_id
    LEFT JOIN dbo.txn_attendance_details d ON d.attendance_id = a.attendance_id
    WHERE CAST(a.attendance_date AS DATE) = @date
    GROUP BY
        a.attendance_id,
        a.attendance_date,
        c.class_name,
        t.first_name,
        t.last_name
    ORDER BY a.attendance_date DESC
    OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

            var list = new List<object>();
            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@date", date);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);

                using var r = cmd.ExecuteReader();
                while (r.Read())
                {
                    list.Add(new
                    {
                        attendance_id = r["attendance_id"].ToString(),
                        attendance_date = ((DateTime)r["attendance_date"]).ToString("yyyy-MM-dd"),
                        class_name = r["class_name"].ToString(),
                        teacher_name = r["teacher_name"].ToString(),
                        present = Convert.ToInt32(r["present"]),
                        sick = Convert.ToInt32(r["sick"]),
                        permit = Convert.ToInt32(r["permit"]),
                        alpha = Convert.ToInt32(r["alpha"])
                    });
                }
            }

            return Json(new
            {
                draw,
                recordsTotal,
                recordsFiltered = recordsTotal,
                data = list
            });
        }

        // ParseDataTablesQuery method (same as ClassController)
        private (int draw, int start, int length, string searchValue, int orderColumnIndex, string orderDir)
        ParseDataTablesQuery()
        {
            var q = Request.Query;
            int.TryParse(q["draw"], out var draw);
            if (draw <= 0) draw = 1;
            int.TryParse(q["start"], out var start);
            if (start < 0) start = 0;
            int.TryParse(q["length"], out var length);
            if (length <= 0) length = 10;
            var searchValue = q["search[value]"].ToString() ?? string.Empty;

            int orderColumnIndex = 0; // default to column 0 (Date)
            var orderColIdxStr = q["order[0][column]"].ToString();
            if (int.TryParse(orderColIdxStr, out var idx))
            {
                orderColumnIndex = idx;
            }

            var dir = q["order[0][dir]"].ToString();
            var orderDir = "ASC";
            if (!string.IsNullOrWhiteSpace(dir) &&
                (dir.Equals("asc", StringComparison.OrdinalIgnoreCase) ||
                 dir.Equals("desc", StringComparison.OrdinalIgnoreCase)))
            {
                orderDir = dir.ToUpper();
            }

            return (draw, start, length, searchValue, orderColumnIndex, orderDir);
        }

        [HttpGet]
        public IActionResult GetById(string id)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(id))
                {
                    return Json(DTOResponse.fail("Invalid attendance ID", 400));
                }

                using var conn = GetConn();
                conn.Open();

                // ===== Header Attendance =====
                var headerSql = @"
            SELECT 
                a.attendance_id,
                a.attendance_date,
                a.academic_class_id,
                a.teacher_id,
                c.class_name
            FROM txn_attendances a
            LEFT JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c ON ac.class_id = c.class_id
            WHERE a.attendance_id = @id";

                object header = null;

                using (var cmd = new SqlCommand(headerSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var reader = cmd.ExecuteReader();

                    if (reader.Read())
                    {
                        header = new
                        {
                            attendance_id = reader["attendance_id"]?.ToString(),
                            attendance_date = reader["attendance_date"] == DBNull.Value
                                ? ""
                                : Convert.ToDateTime(reader["attendance_date"]).ToString("yyyy-MM-dd"),
                            academic_class_id = reader["academic_class_id"]?.ToString(),
                            teacher_id = reader["teacher_id"]?.ToString(),
                            class_name = reader["class_name"]?.ToString()
                        };
                    }
                    reader.Close();
                }

                if (header == null)
                {
                    return Json(DTOResponse.fail("Attendance data not found", 404));
                }

                // ===== Details Attendance =====
                var details = new List<object>();

                var detailsSql = @"
            SELECT 
                d.attendance_detail_id,
                d.student_id,
                d.status,
                d.notes,
                COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) AS student_name,
                s.nis,
                s.profile_photo
            FROM txn_attendance_details d
            LEFT JOIN mst_students s ON d.student_id = s.student_id
            WHERE d.attendance_id = @id
            ORDER BY student_name";

                using (var cmd = new SqlCommand(detailsSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var reader = cmd.ExecuteReader();

                    while (reader.Read())
                    {
                        details.Add(new
                        {
                            attendance_detail_id = reader["attendance_detail_id"]?.ToString(),
                            student_id = reader["student_id"]?.ToString(),
                            status = reader["status"]?.ToString(),
                            notes = reader["notes"]?.ToString(),
                            student_name = reader["student_name"]?.ToString(),
                            nis = reader["nis"]?.ToString(),
                            profile_photo = reader["profile_photo"]?.ToString()  // path atau URL foto siswa
                        });
                    }
                }

                return Json(DTOResponse.ok(new
                {
                    attendance_id = ((dynamic)header).attendance_id,
                    attendance_date = ((dynamic)header).attendance_date,
                    academic_class_id = ((dynamic)header).academic_class_id,
                    teacher_id = ((dynamic)header).teacher_id,
                    class_name = ((dynamic)header).class_name,
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

                var attendanceDateStr = f["attendance_date"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var teacherId = f["teacher_id"].ToString();

                // Validate required fields
                if (string.IsNullOrWhiteSpace(attendanceDateStr) ||
                    string.IsNullOrWhiteSpace(academicClassId) ||
                    string.IsNullOrWhiteSpace(teacherId))
                {
                    return Json(DTOResponse.fail("Missing required fields", 400));
                }

                if (!DateTime.TryParse(attendanceDateStr, out var attendanceDate))
                {
                    return Json(DTOResponse.fail("Invalid attendance date format", 400));
                }

                // Get arrays from form
                var studentIdsJson = f["student_ids"].ToString();
                var statusesJson = f["statuses"].ToString();
                var notesJson = f["notes"].ToString();

                List<string> studentIds;
                List<string> statuses;
                List<string> notes;

                try
                {
                    studentIds = JsonSerializer.Deserialize<List<string>>(studentIdsJson);
                    statuses = JsonSerializer.Deserialize<List<string>>(statusesJson);
                    notes = JsonSerializer.Deserialize<List<string>>(notesJson);
                }
                catch
                {
                    return Json(DTOResponse.fail("Invalid student details format", 400));
                }

                if (studentIds == null || studentIds.Count == 0 ||
                    statuses.Count != studentIds.Count ||
                    notes.Count != studentIds.Count)
                {
                    return Json(DTOResponse.fail("Invalid or mismatched student details", 400));
                }

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                // Generate attendance_id (similar to your grade_id logic)
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(attendance_id),'ATT0000') FROM txn_attendances",
                    conn, trx);
                var seq = int.Parse(lastCmd.ExecuteScalar().ToString().Substring(3)) + 1;
                var attendanceId = "ATT" + seq.ToString("D4");

                // INSERT HEADER
                var headerSql = @"
            INSERT INTO txn_attendances
            (
                attendance_id,
                attendance_date,
                academic_class_id,
                teacher_id,
                created_at,
                created_by   -- optional, if you have user tracking
            )
            VALUES
            (
                @id,
                @date,
                @classId,
                @teacherId,
                GETDATE(),
                @createdBy
            )";

                using (var cmd = new SqlCommand(headerSql, conn, trx))
                {
                    cmd.Parameters.AddWithValue("@id", attendanceId);
                    cmd.Parameters.AddWithValue("@date", attendanceDate);
                    cmd.Parameters.AddWithValue("@classId", academicClassId);
                    cmd.Parameters.AddWithValue("@teacherId", teacherId);
                    cmd.Parameters.AddWithValue("@createdBy", User.Identity?.Name ?? "SYSTEM"); // adjust as needed
                    cmd.ExecuteNonQuery();
                }

                // INSERT DETAILS
                var detCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(attendance_detail_id),'ATD0000') FROM txn_attendance_details",
                    conn, trx);
                var detSeq = int.Parse(detCmd.ExecuteScalar().ToString().Substring(3));

                for (int i = 0; i < studentIds.Count; i++)
                {
                    detSeq++;
                    var detailId = "ATD" + detSeq.ToString("D4");

                    var detailSql = @"
                INSERT INTO txn_attendance_details
                (
                    attendance_detail_id,
                    attendance_id,
                    student_id,
                    status,
                    notes,
                    created_at,
                    created_by
                )
                VALUES
                (
                    @did,
                    @aid,
                    @sid,
                    @status,
                    @notes,
                    GETDATE(),
                    @createdBy
                )";

                    using var dcmd = new SqlCommand(detailSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detailId);
                    dcmd.Parameters.AddWithValue("@aid", attendanceId);
                    dcmd.Parameters.AddWithValue("@sid", studentIds[i]);
                    dcmd.Parameters.AddWithValue("@status", statuses[i]);
                    dcmd.Parameters.AddWithValue("@notes", (object)notes[i] ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@createdBy", User.Identity?.Name ?? "SYSTEM");

                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "Attendance created successfully"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Update()
        {
            try
            {
                var f = Request.Form;

                var attendanceId = f["attendance_id"].ToString();
                var attendanceDateStr = f["attendance_date"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(attendanceId))
                    return Json(DTOResponse.fail("Invalid attendance ID", 400));

                if (!DateTime.TryParse(attendanceDateStr, out var attendanceDate))
                    return Json(DTOResponse.fail("Invalid attendance date format", 400));

                if (string.IsNullOrWhiteSpace(academicClassId) || string.IsNullOrWhiteSpace(teacherId))
                    return Json(DTOResponse.fail("Class and Teacher are required", 400));

                // Parse details JSON
                List<dynamic> details;
                try
                {
                    using var json = JsonDocument.Parse(rawDetails);
                    details = json.RootElement
                        .EnumerateArray()
                        .Select(x => new
                        {
                            student_id = x.GetProperty("student_id").GetString(),
                            status = x.GetProperty("status").GetString(),
                            notes = x.TryGetProperty("notes", out var n) ? n.GetString() : null
                        })
                        .Where(d => !string.IsNullOrWhiteSpace(d.student_id) && !string.IsNullOrWhiteSpace(d.status))
                        .Cast<dynamic>()
                        .ToList();
                }
                catch
                {
                    return Json(DTOResponse.fail("Invalid details format", 400));
                }

                if (details.Count == 0)
                    return Json(DTOResponse.fail("At least one attendance detail is required", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                // UPDATE HEADER
                var headerSql = @"
            UPDATE txn_attendances SET
                attendance_date    = @date,
                academic_class_id  = @cls,
                teacher_id         = @tch,
                updated_at         = GETDATE()
                -- updated_by      = @updatedBy   (add if you have user tracking)
            WHERE attendance_id = @id";

                using (var cmd = new SqlCommand(headerSql, conn, trx))
                {
                    cmd.Parameters.AddWithValue("@id", attendanceId);
                    cmd.Parameters.AddWithValue("@date", attendanceDate);
                    cmd.Parameters.AddWithValue("@cls", academicClassId);
                    cmd.Parameters.AddWithValue("@tch", teacherId);
                    // cmd.Parameters.AddWithValue("@updatedBy", User.Identity?.Name ?? "SYSTEM");
                    cmd.ExecuteNonQuery();
                }

                // DELETE OLD DETAILS
                using (var del = new SqlCommand(
                    "DELETE FROM txn_attendance_details WHERE attendance_id = @id",
                    conn, trx))
                {
                    del.Parameters.AddWithValue("@id", attendanceId);
                    del.ExecuteNonQuery();
                }

                // INSERT NEW DETAILS
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(attendance_detail_id), 'ATD0000') FROM txn_attendance_details",
                    conn, trx);
                var seq = int.Parse(lastCmd.ExecuteScalar().ToString().Substring(3));

                foreach (var d in details)
                {
                    seq++;
                    var detailId = "ATD" + seq.ToString("D4");

                    var detailSql = @"
                INSERT INTO txn_attendance_details
                (
                    attendance_detail_id,
                    attendance_id,
                    student_id,
                    status,
                    notes,
                    created_at,
                    updated_at
                    -- created_by, updated_by   (add if needed)
                )
                VALUES
                (
                    @did,
                    @aid,
                    @sid,
                    @status,
                    @notes,
                    GETDATE(),
                    GETDATE()
                )";

                    using var dcmd = new SqlCommand(detailSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detailId);
                    dcmd.Parameters.AddWithValue("@aid", attendanceId);
                    dcmd.Parameters.AddWithValue("@sid", d.student_id);
                    dcmd.Parameters.AddWithValue("@status", d.status);
                    dcmd.Parameters.AddWithValue("@notes", (object)d.notes ?? DBNull.Value);
                    // dcmd.Parameters.AddWithValue("@createdBy", User.Identity?.Name ?? "SYSTEM");
                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "Attendance updated successfully"));
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
                    return Json(DTOResponse.fail("invalid attendance id", 400));

                using var conn = GetConn();
                conn.Open();

                var delDet = new SqlCommand(
                    "DELETE FROM txn_attendance_details WHERE attendance_id=@id",
                    conn
                );
                delDet.Parameters.AddWithValue("@id", req.id);
                delDet.ExecuteNonQuery();

                var cmd = new SqlCommand(
                    "DELETE FROM txn_attendances WHERE attendance_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);
                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "attendance deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}