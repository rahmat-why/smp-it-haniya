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
            ViewBag.ClassOptions = GetClassOptions();
            ViewBag.TeacherOptions = GetTeacherOptions();
            ViewBag.StudentOptions = GetStudentOptions();
            return View("~/Views/PortalAdmin/Attendance/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.attendanceId = id;
            ViewBag.ClassOptions = GetClassOptions();
            ViewBag.TeacherOptions = GetTeacherOptions();
            ViewBag.StudentOptions = GetStudentOptions();
            return View("~/Views/PortalAdmin/Attendance/Edit.cshtml");
        }

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
                a.attendance_id,
                a.attendance_date,
                c.class_name,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                COUNT(d.attendance_detail_id) AS detail_count
            FROM txn_attendances a
            LEFT JOIN mst_academic_classes ac
                ON a.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c
                ON ac.class_id = c.class_id
            LEFT JOIN mst_teachers t
                ON a.teacher_id = t.teacher_id
            LEFT JOIN txn_attendance_details d
                ON a.attendance_id = d.attendance_id
            GROUP BY
                a.attendance_id,
                a.attendance_date,
                c.class_name,
                t.first_name,
                t.last_name
            ORDER BY
                a.attendance_date DESC,
                c.class_name";

                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();

                while (rd.Read())
                {
                    list.Add(new
                    {
                        attendance_id = rd["attendance_id"]?.ToString(),
                        attendance_date = Convert.ToDateTime(rd["attendance_date"]).ToString("yyyy-MM-dd"),
                        class_name = rd["class_name"]?.ToString(),
                        teacher_name = rd["teacher_name"]?.ToString(),
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
                    return Json(DTOResponse.fail("invalid attendance id", 400));

                using var conn = GetConn();
                conn.Open();

                // ===== header =====
                var sql = @"
            SELECT
                a.attendance_id,
                a.attendance_date,
                a.academic_class_id,
                a.teacher_id,
                c.class_name
            FROM txn_attendances a
            LEFT JOIN mst_academic_classes ac
                ON a.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c
                ON ac.class_id = c.class_id
            WHERE a.attendance_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                var attendance = new
                {
                    attendance_id = rd["attendance_id"]?.ToString(),
                    attendance_date = rd["attendance_date"] == DBNull.Value
                        ? ""
                        : Convert.ToDateTime(rd["attendance_date"]).ToString("yyyy-MM-dd"),
                    academic_class_id = rd["academic_class_id"]?.ToString(),
                    teacher_id = rd["teacher_id"]?.ToString(),
                    class_name = rd["class_name"]?.ToString()
                };

                rd.Close();

                // ===== details =====
                var details = new List<object>();

                var dsql = @"
            SELECT
                d.attendance_detail_id,
                d.student_id,
                d.status,
                d.notes,
                COALESCE(s.full_name, CONCAT(s.first_name, ' ', s.last_name)) AS student_name
            FROM txn_attendance_details d
            LEFT JOIN mst_students s
                ON d.student_id = s.student_id
            WHERE d.attendance_id = @id
            ORDER BY student_name";

                using var dcmd = new SqlCommand(dsql, conn);
                dcmd.Parameters.AddWithValue("@id", id);

                using var drd = dcmd.ExecuteReader();
                while (drd.Read())
                {
                    details.Add(new
                    {
                        attendance_detail_id = drd["attendance_detail_id"]?.ToString(),
                        student_id = drd["student_id"]?.ToString(),
                        status = drd["status"]?.ToString(),
                        notes = drd["notes"]?.ToString(),
                        student_name = drd["student_name"]?.ToString()
                    });
                }

                return Json(DTOResponse.ok(new
                {
                    attendance.attendance_id,
                    attendance.attendance_date,
                    attendance.academic_class_id,
                    attendance.teacher_id,
                    attendance.class_name,
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
                var subjectId = f["subject_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var gradeType = f["grade_type"].ToString();
                var minimumValueStr = f["minimum_value"].ToString();
                var rawDetails = f["details"].ToString();

                /* ================= VALIDATION ================= */

                if (string.IsNullOrWhiteSpace(academicClassId) ||
                    string.IsNullOrWhiteSpace(subjectId) ||
                    string.IsNullOrWhiteSpace(teacherId) ||
                    string.IsNullOrWhiteSpace(gradeType))
                {
                    return Json(DTOResponse.fail("required fields missing", 400));
                }

                decimal? minimumValue = null;
                if (!string.IsNullOrWhiteSpace(minimumValueStr) &&
                    decimal.TryParse(minimumValueStr, NumberStyles.Any, CultureInfo.InvariantCulture, out var mv))
                {
                    minimumValue = mv;
                }

                /* ================= PARSE DETAILS ================= */

                List<dynamic> details;
                try
                {
                    using var json = JsonDocument.Parse(rawDetails);
                    details = json.RootElement
                        .EnumerateArray()
                        .Select(x =>
                        {
                            decimal? gradeValue = null;

                            if (
                                x.TryGetProperty("grade_value", out var gv) &&
                                decimal.TryParse(
                                    gv.GetString(),
                                    NumberStyles.Any,
                                    CultureInfo.InvariantCulture,
                                    out var parsed
                                )
                            )
                            {
                                gradeValue = parsed;
                            }

                            return new
                            {
                                student_id = x.GetProperty("student_id").GetString(),
                                grade_value = gradeValue,
                                grade_attitude = x.TryGetProperty("grade_attitude", out var ga)
                                    ? ga.GetString()
                                    : null,
                                notes = x.TryGetProperty("notes", out var n)
                                    ? n.GetString()
                                    : null
                            };
                        })
                        .Where(d => !string.IsNullOrWhiteSpace(d.student_id))
                        .Cast<dynamic>()
                        .ToList();
                }
                catch
                {
                    return Json(DTOResponse.fail("invalid details format", 400));
                }

                if (details.Count == 0)
                    return Json(DTOResponse.fail("at least one detail required", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                /* ================= GENERATE GRADE ID ================= */

                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(grade_id),'GRD0000') FROM txn_grades",
                    conn,
                    trx
                );

                var seq = int.Parse(lastCmd.ExecuteScalar().ToString().Substring(3)) + 1;
                var gradeId = "GRD" + seq.ToString("D4");

                /* ================= INSERT HEADER ================= */

                var sql = @"
            INSERT INTO txn_grades
            (
                grade_id,
                academic_class_id,
                subject_id,
                teacher_id,
                grade_type,
                minimum_value,
                created_at
            )
            VALUES
            (
                @id,
                @cls,
                @sub,
                @tch,
                @type,
                @min,
                GETDATE()
            )";

                using (var cmd = new SqlCommand(sql, conn, trx))
                {
                    cmd.Parameters.AddWithValue("@id", gradeId);
                    cmd.Parameters.AddWithValue("@cls", academicClassId);
                    cmd.Parameters.AddWithValue("@sub", subjectId);
                    cmd.Parameters.AddWithValue("@tch", teacherId);
                    cmd.Parameters.AddWithValue("@type", gradeType);
                    cmd.Parameters.Add("@min", SqlDbType.Decimal).Value =
                        (object?)minimumValue ?? DBNull.Value;

                    cmd.ExecuteNonQuery();
                }

                /* ================= INSERT DETAILS ================= */

                var detCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(grade_detail_id),'GDL0000') FROM txn_grade_details",
                    conn,
                    trx
                );

                var detSeq = int.Parse(detCmd.ExecuteScalar().ToString().Substring(3));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "GDL" + detSeq.ToString("D4");

                    var dsql = @"
                INSERT INTO txn_grade_details
                (
                    grade_detail_id,
                    grade_id,
                    student_id,
                    grade_value,
                    grade_attitude,
                    notes,
                    created_at
                )
                VALUES
                (
                    @did,
                    @gid,
                    @stu,
                    @val,
                    @att,
                    @nts,
                    GETDATE()
                )";

                    using var dcmd = new SqlCommand(dsql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@gid", gradeId);
                    dcmd.Parameters.AddWithValue("@stu", d.student_id);

                    var p = dcmd.Parameters.Add("@val", SqlDbType.Decimal);
                    p.Precision = 5;
                    p.Scale = 2;
                    p.Value = (object?)d.grade_value ?? DBNull.Value;

                    dcmd.Parameters.AddWithValue("@att", (object?)d.grade_attitude ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@nts", (object?)d.notes ?? DBNull.Value);

                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "grade created"));
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

                var gradeId = f["grade_id"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var subjectId = f["subject_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var gradeType = f["grade_type"].ToString();
                var minimumValueStr = f["minimum_value"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(gradeId))
                    return Json(DTOResponse.fail("invalid grade id", 400));

                decimal? minimumValue = null;
                if (!string.IsNullOrWhiteSpace(minimumValueStr) &&
                    decimal.TryParse(minimumValueStr, NumberStyles.Any, CultureInfo.InvariantCulture, out var mv))
                {
                    minimumValue = mv;
                }

                /* ================= PARSE DETAILS ================= */

                List<dynamic> details;
                try
                {
                    using var json = JsonDocument.Parse(rawDetails);
                    details = json.RootElement
                        .EnumerateArray()
                        .Select(x =>
                        {
                            decimal? gradeValue = null;

                            if (
                                x.TryGetProperty("grade_value", out var gv) &&
                                decimal.TryParse(
                                    gv.GetString(),
                                    NumberStyles.Any,
                                    CultureInfo.InvariantCulture,
                                    out var parsed
                                )
                            )
                            {
                                gradeValue = parsed;
                            }

                            return new
                            {
                                student_id = x.GetProperty("student_id").GetString(),
                                grade_value = gradeValue,
                                grade_attitude = x.TryGetProperty("grade_attitude", out var ga)
                                    ? ga.GetString()
                                    : null,
                                notes = x.TryGetProperty("notes", out var n)
                                    ? n.GetString()
                                    : null
                            };
                        })
                        .Where(d => !string.IsNullOrWhiteSpace(d.student_id))
                        .Cast<dynamic>()
                        .ToList();
                }
                catch
                {
                    return Json(DTOResponse.fail("invalid details format", 400));
                }

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                /* ================= UPDATE HEADER ================= */

                var sql = @"
            UPDATE txn_grades SET
                academic_class_id = @cls,
                subject_id = @sub,
                teacher_id = @tch,
                grade_type = @type,
                minimum_value = @min,
                updated_at = GETDATE()
            WHERE grade_id = @id";

                using (var cmd = new SqlCommand(sql, conn, trx))
                {
                    cmd.Parameters.AddWithValue("@id", gradeId);
                    cmd.Parameters.AddWithValue("@cls", academicClassId);
                    cmd.Parameters.AddWithValue("@sub", subjectId);
                    cmd.Parameters.AddWithValue("@tch", teacherId);
                    cmd.Parameters.AddWithValue("@type", gradeType);
                    cmd.Parameters.Add("@min", SqlDbType.Decimal).Value =
                        (object?)minimumValue ?? DBNull.Value;

                    cmd.ExecuteNonQuery();
                }

                /* ================= DELETE OLD DETAILS ================= */

                using (var del = new SqlCommand(
                    "DELETE FROM txn_grade_details WHERE grade_id=@id",
                    conn,
                    trx))
                {
                    del.Parameters.AddWithValue("@id", gradeId);
                    del.ExecuteNonQuery();
                }

                /* ================= INSERT DETAILS ================= */

                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(grade_detail_id),'GDL0000') FROM txn_grade_details",
                    conn,
                    trx
                );

                var seq = int.Parse(lastCmd.ExecuteScalar().ToString().Substring(3));

                foreach (var d in details)
                {
                    seq++;
                    var detId = "GDL" + seq.ToString("D4");

                    var dsql = @"
                INSERT INTO txn_grade_details
                (
                    grade_detail_id,
                    grade_id,
                    student_id,
                    grade_value,
                    grade_attitude,
                    notes,
                    created_at
                )
                VALUES
                (
                    @did,
                    @gid,
                    @stu,
                    @val,
                    @att,
                    @nts,
                    GETDATE()
                )";

                    using var dcmd = new SqlCommand(dsql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@gid", gradeId);
                    dcmd.Parameters.AddWithValue("@stu", d.student_id);

                    var p = dcmd.Parameters.Add("@val", SqlDbType.Decimal);
                    p.Precision = 5;
                    p.Scale = 2;
                    p.Value = (object?)d.grade_value ?? DBNull.Value;

                    dcmd.Parameters.AddWithValue("@att", (object?)d.grade_attitude ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@nts", (object?)d.notes ?? DBNull.Value);

                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "grade updated"));
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