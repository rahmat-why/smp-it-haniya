using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Linq;
using System.Text.Json;
using Haniya.Models;

namespace Haniya.Controllers.PortalAdmin
{
    public class GradeController : Controller
    {
        private readonly IConfiguration _config;

        public GradeController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        // ===== MASTER LOADERS =====

        private List<dynamic> GetClassOptions()
        {
            var list = new List<dynamic>();
            using var conn = GetConn();
            conn.Open();

            var sql = @"
                SELECT c.academic_class_id, cl.class_name
                FROM mst_academic_classes c
                LEFT JOIN mst_classes cl ON cl.class_id = c.class_id
                ORDER BY cl.class_name";

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
            return View("~/Views/PortalAdmin/Grade/Index.cshtml");
        }

        public IActionResult Create()
        {
            ViewBag.ClassOptions = GetClassOptions();
            ViewBag.SubjectOptions = GetSubjectOptions();
            ViewBag.TeacherOptions = GetTeacherOptions();
            ViewBag.StudentOptions = GetStudentOptions();
            return View("~/Views/PortalAdmin/Grade/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.gradeId = id;
            ViewBag.ClassOptions = GetClassOptions();
            ViewBag.SubjectOptions = GetSubjectOptions();
            ViewBag.TeacherOptions = GetTeacherOptions();
            ViewBag.StudentOptions = GetStudentOptions();
            return View("~/Views/PortalAdmin/Grade/Edit.cshtml");
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
                g.grade_id,
                g.grade_type,
                cl.class_name,
                s.subject_name,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                COUNT(DISTINCT d.student_id) AS total_student
            FROM txn_grades g
            LEFT JOIN mst_academic_classes c
                ON g.academic_class_id = c.academic_class_id
            LEFT JOIN mst_classes cl
                ON cl.class_id = c.class_id
            LEFT JOIN mst_subjects s
                ON g.subject_id = s.subject_id
            LEFT JOIN mst_teachers t
                ON g.teacher_id = t.teacher_id
            LEFT JOIN txn_grade_details d
                ON g.grade_id = d.grade_id
            GROUP BY
                g.grade_id,
                g.grade_type,
                cl.class_name,
                s.subject_name,
                CONCAT(t.first_name, ' ', t.last_name)
            ORDER BY
                cl.class_name,
                s.subject_name,
                g.grade_type";

                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();

                while (rd.Read())
                {
                    list.Add(new
                    {
                        grade_id = rd["grade_id"]?.ToString(),
                        grade_type = rd["grade_type"]?.ToString(),
                        class_name = rd["class_name"]?.ToString(),
                        subject_name = rd["subject_name"]?.ToString(),
                        teacher_name = rd["teacher_name"]?.ToString(),
                        total_student = Convert.ToInt32(rd["total_student"])
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
                using var conn = GetConn();
                conn.Open();

                // ===== HEADER =====
                var hdrSql = @"
            SELECT
                grade_id,
                subject_id,
                teacher_id,
                grade_type,
                academic_class_id,
                minimum_value
            FROM txn_grades
            WHERE grade_id = @id";

                using var hdrCmd = new SqlCommand(hdrSql, conn);
                hdrCmd.Parameters.AddWithValue("@id", id);

                using var rd = hdrCmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                var header = new
                {
                    grade_id = rd["grade_id"]?.ToString(),
                    subject_id = rd["subject_id"]?.ToString(),
                    teacher_id = rd["teacher_id"]?.ToString(),
                    grade_type = rd["grade_type"]?.ToString(),
                    academic_class_id = rd["academic_class_id"]?.ToString(),
                    minimum_value = rd["minimum_value"]?.ToString()
                };
                rd.Close();

                // ===== DETAILS =====
                var details = new List<object>();

                var detSql = @"
            SELECT
                d.grade_detail_id,
                d.student_id,
                s.full_name AS student_name,
                d.grade_value,
                d.grade_attitude,
                d.notes
            FROM txn_grade_details d
            LEFT JOIN mst_students s
                ON d.student_id = s.student_id
            WHERE d.grade_id = @id
            ORDER BY s.full_name";

                using var detCmd = new SqlCommand(detSql, conn);
                detCmd.Parameters.AddWithValue("@id", id);

                using var drd = detCmd.ExecuteReader();
                while (drd.Read())
                {
                    details.Add(new
                    {
                        grade_detail_id = drd["grade_detail_id"]?.ToString(),
                        student_id = drd["student_id"]?.ToString(),
                        student_name = drd["student_name"]?.ToString(),
                        grade_value = drd["grade_value"]?.ToString(),
                        grade_attitude = drd["grade_attitude"]?.ToString(),
                        notes = drd["notes"]?.ToString()
                    });
                }

                return Json(DTOResponse.ok(new
                {
                    header.grade_id,
                    header.subject_id,
                    header.teacher_id,
                    header.grade_type,
                    header.academic_class_id,
                    header.minimum_value,
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
                var teacherId = f["teacher_id"].ToString();
                var gradeType = f["grade_type"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var minValStr = f["minimum_value"].ToString();
                var rawDetails = f["details"].ToString();

                // ===== validations =====
                if (string.IsNullOrWhiteSpace(subjectId))
                    return Json(DTOResponse.fail("subject is required", 400));

                if (string.IsNullOrWhiteSpace(teacherId))
                    return Json(DTOResponse.fail("teacher is required", 400));

                if (string.IsNullOrWhiteSpace(gradeType))
                    return Json(DTOResponse.fail("grade type is required", 400));

                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("class is required", 400));

                if (string.IsNullOrWhiteSpace(rawDetails))
                    return Json(DTOResponse.fail("grade details are required", 400));

                double? minVal = null;
                if (!string.IsNullOrWhiteSpace(minValStr) &&
                    double.TryParse(minValStr, out var mv))
                {
                    minVal = mv;
                }

                using var conn = GetConn();
                conn.Open();

                // ===== generate grade_id =====
                var lastIdCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(grade_id),'GRD0000') FROM txn_grades", conn);
                var lastId = lastIdCmd.ExecuteScalar()?.ToString() ?? "GRD0000";
                var next = int.Parse(lastId.Substring(3)) + 1;
                var gradeId = "GRD" + next.ToString("D4");

                // ===== insert header =====
                var sql = @"
            INSERT INTO txn_grades (
                grade_id,
                subject_id,
                teacher_id,
                grade_type,
                academic_class_id,
                minimum_value,
                created_at
            ) VALUES (
                @id,
                @sub,
                @tch,
                @type,
                @cls,
                @min,
                GETDATE()
            )";

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", gradeId);
                    cmd.Parameters.AddWithValue("@sub", subjectId);
                    cmd.Parameters.AddWithValue("@tch", teacherId);
                    cmd.Parameters.AddWithValue("@type", gradeType);
                    cmd.Parameters.AddWithValue("@cls", academicClassId);
                    cmd.Parameters.AddWithValue("@min", (object?)minVal ?? DBNull.Value);
                    cmd.ExecuteNonQuery();
                }

                // ===== insert details (NO DTO) =====
                InsertGradeDetails(conn, gradeId, rawDetails);

                return Json(DTOResponse.ok(null, "grade created"));
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

                var gradeId = f["grade_id"].ToString();
                var subjectId = f["subject_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var gradeType = f["grade_type"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var minValStr = f["minimum_value"].ToString();
                var rawDetails = f["details"].ToString();

                // ===== validations =====
                if (string.IsNullOrWhiteSpace(gradeId))
                    return Json(DTOResponse.fail("invalid grade id", 400));

                if (string.IsNullOrWhiteSpace(subjectId))
                    return Json(DTOResponse.fail("subject is required", 400));

                if (string.IsNullOrWhiteSpace(teacherId))
                    return Json(DTOResponse.fail("teacher is required", 400));

                if (string.IsNullOrWhiteSpace(gradeType))
                    return Json(DTOResponse.fail("grade type is required", 400));

                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("class is required", 400));

                if (string.IsNullOrWhiteSpace(rawDetails))
                    return Json(DTOResponse.fail("grade detail required", 400));

                double? minVal = null;
                if (!string.IsNullOrWhiteSpace(minValStr) &&
                    double.TryParse(minValStr, out var mv))
                {
                    minVal = mv;
                }

                using var conn = GetConn();
                conn.Open();

                // ===== update header =====
                var sql = @"
            UPDATE txn_grades SET
                subject_id = @sub,
                teacher_id = @tch,
                grade_type = @type,
                academic_class_id = @cls,
                minimum_value = @min,
                updated_at = GETDATE()
            WHERE grade_id = @id";

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", gradeId);
                    cmd.Parameters.AddWithValue("@sub", subjectId);
                    cmd.Parameters.AddWithValue("@tch", teacherId);
                    cmd.Parameters.AddWithValue("@type", gradeType);
                    cmd.Parameters.AddWithValue("@cls", academicClassId);
                    cmd.Parameters.AddWithValue("@min", (object?)minVal ?? DBNull.Value);
                    cmd.ExecuteNonQuery();
                }

                // ===== delete old details =====
                using (var del = new SqlCommand(
                    "DELETE FROM txn_grade_details WHERE grade_id=@id", conn))
                {
                    del.Parameters.AddWithValue("@id", gradeId);
                    del.ExecuteNonQuery();
                }

                // ===== insert new details (NO DTO) =====
                InsertGradeDetails(conn, gradeId, rawDetails);

                return Json(DTOResponse.ok(null, "grade updated"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Delete()
        {
            try
            {
                var id = Request.Form["id"].ToString();
                if (string.IsNullOrWhiteSpace(id))
                    return Json(DTOResponse.fail("invalid grade id", 400));

                using var conn = GetConn();
                conn.Open();

                new SqlCommand(
                    "DELETE FROM txn_grade_details WHERE grade_id=@id", conn)
                { Parameters = { new("@id", id) } }
                .ExecuteNonQuery();

                new SqlCommand(
                    "DELETE FROM txn_grades WHERE grade_id=@id", conn)
                { Parameters = { new("@id", id) } }
                .ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "grade deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        private void InsertGradeDetails(
    SqlConnection conn,
    string gradeId,
    string rawDetails
)
        {
            // ===== parse details (NO DTO) =====
            List<dynamic> details;
            try
            {
                using var json = JsonDocument.Parse(rawDetails);
                details = json.RootElement
                    .EnumerateArray()
                    .Select(x => new
                    {
                        student_id = x.GetProperty("student_id").GetString(),
                        grade_value = x.TryGetProperty("grade_value", out JsonElement gvEl)
                                        ? gvEl.GetString()
                                        : null,
                        grade_attitude = x.TryGetProperty("grade_attitude", out JsonElement gaEl)
                                        ? gaEl.GetString()
                                        : null,
                        notes = x.TryGetProperty("notes", out JsonElement ntEl)
                                        ? ntEl.GetString()
                                        : null
                    })
                    .Where(x => !string.IsNullOrWhiteSpace(x.student_id))
                    .Cast<dynamic>()
                    .ToList();
            }
            catch
            {
                throw new Exception("invalid grade details format");
            }

            if (details.Count == 0)
                throw new Exception("at least one grade detail is required");

            // ===== generate detail id =====
            var lastCmd = new SqlCommand(
                "SELECT ISNULL(MAX(grade_detail_id),'GDL0000') FROM txn_grade_details",
                conn
            );
            var lastId = lastCmd.ExecuteScalar()?.ToString() ?? "GDL0000";
            var seq = int.Parse(lastId.Substring(3));

            foreach (var d in details)
            {
                seq++;
                var detId = "GDL" + seq.ToString("D4");

                var sql = @"
            INSERT INTO txn_grade_details (
                grade_detail_id,
                grade_id,
                student_id,
                grade_value,
                grade_attitude,
                notes,
                created_at
            ) VALUES (
                @id,
                @gid,
                @stu,
                @val,
                @att,
                @nts,
                GETDATE()
            )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", detId);
                cmd.Parameters.AddWithValue("@gid", gradeId);
                cmd.Parameters.AddWithValue("@stu", d.student_id);
                cmd.Parameters.AddWithValue("@val", (object?)d.grade_value ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@att", (object?)d.grade_attitude ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@nts", (object?)d.notes ?? DBNull.Value);
                cmd.ExecuteNonQuery();
            }
        }
    }
}