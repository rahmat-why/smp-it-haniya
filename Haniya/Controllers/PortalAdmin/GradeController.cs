using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Text.Json;
using Haniya.Models;
using System.Data;

namespace Haniya.Controllers.PortalAdmin
{
    public class GradeController : Controller
    {
        private readonly IConfiguration _config;
        public GradeController(IConfiguration config) => _config = config;

        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        public IActionResult Index() => View("~/Views/PortalAdmin/Grade/Index.cshtml");
        public IActionResult Create() => View("~/Views/PortalAdmin/Grade/Create.cshtml");
        public IActionResult Edit(string id)
        {
            ViewBag.gradeId = id;
            return View("~/Views/PortalAdmin/Grade/Edit.cshtml");
        }

        public IActionResult GetAll(string academic_year_id = null, string academic_class_id = null, string grade_type = null)
        {
            var (draw, start, length, search, _, _) = ParseDataTablesQuery();

            using var conn = GetConn();
            conn.Open();

            if (string.IsNullOrWhiteSpace(academic_year_id))
            {
                using var activeYearCmd = new SqlCommand(@"
                    SELECT TOP 1 academic_year_id
                    FROM mst_academic_years
                    WHERE status = 'ACTIVE'
                    ORDER BY start_date DESC", conn);
                academic_year_id = activeYearCmd.ExecuteScalar()?.ToString();
            }

            var searchKeyword = string.IsNullOrWhiteSpace(search) ? null : $"%{search.Trim()}%";

            // ================= TOTAL =================
            var totalSql = @"
        SELECT COUNT(*) 
        FROM txn_grades g
        JOIN mst_academic_classes ac ON g.academic_class_id = ac.academic_class_id
        WHERE (@classId IS NULL OR g.academic_class_id = @classId)
          AND (@academicYearId IS NULL OR ac.academic_year_id = @academicYearId)
          AND (@gradeType IS NULL OR g.grade_type = @gradeType)";

            int recordsTotal;

            using (var cmd = new SqlCommand(totalSql, conn))
            {
                cmd.Parameters.AddWithValue("@classId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@academicYearId", (object)academic_year_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@gradeType", (object)grade_type ?? DBNull.Value);

                recordsTotal = (int)cmd.ExecuteScalar();
            }


            // ================= MAIN QUERY =================
            var sql = @"
        SELECT 
            g.grade_id,
            g.grade_date,
            ay.start_date,
            ay.end_date,
            ay.semester,
            c.class_name,
            s.subject_name,
            CONCAT(t.first_name,' ',t.last_name) AS teacher_name,
            COALESCE(dt.item_desc, g.grade_type) AS grade_type_desc,
            ISNULL(r.minimum_value, 0) AS minimum_value,

            COUNT(d.grade_detail_id) AS total_graded,

            -- PASSED
            SUM(
                CASE 
                    WHEN TRY_CAST(REPLACE(d.grade_value, ',', '.') AS FLOAT) 
                         >= ISNULL(r.minimum_value,0)
                    THEN 1 
                    ELSE 0 
                END
            ) AS passed,

            -- REMEDIAL
            SUM(
                CASE 
                    WHEN TRY_CAST(REPLACE(d.grade_value, ',', '.') AS FLOAT) 
                         < ISNULL(r.minimum_value,0)
                         OR TRY_CAST(REPLACE(d.grade_value, ',', '.') AS FLOAT) IS NULL
                    THEN 1 
                    ELSE 0 
                END
            ) AS remedial

        FROM txn_grades g

        JOIN mst_academic_classes ac 
            ON g.academic_class_id = ac.academic_class_id

        JOIN mst_academic_years ay
            ON ac.academic_year_id = ay.academic_year_id

        JOIN mst_classes c 
            ON ac.class_id = c.class_id

        JOIN mst_subjects s 
            ON g.subject_id = s.subject_id

        LEFT JOIN mst_rps r 
            ON r.academic_class_id = ac.academic_class_id 
           AND r.subject_id = g.subject_id

        JOIN mst_teachers t 
            ON g.teacher_id = t.teacher_id

        LEFT JOIN txn_grade_details d 
            ON d.grade_id = g.grade_id

        LEFT JOIN mst_detail_settings dt 
            ON g.grade_type = dt.detail_id 
           AND dt.header_id = 'GRADE_TYPE'

        WHERE (@classId IS NULL OR g.academic_class_id = @classId)
          AND (@academicYearId IS NULL OR ac.academic_year_id = @academicYearId)
          AND (@gradeType IS NULL OR g.grade_type = @gradeType)
          AND (@search IS NULL 
               OR g.grade_type LIKE @search
               OR dt.item_name LIKE @search
               OR dt.item_desc LIKE @search)

        GROUP BY 
            g.grade_id,
            g.grade_date,
            ay.start_date,
            ay.end_date,
            ay.semester,
            c.class_name,
            s.subject_name,
            t.first_name,
            t.last_name,
            g.grade_type,
            dt.item_desc,
            r.minimum_value

        ORDER BY 
            g.grade_date DESC,
            MAX(g.created_at) DESC

        OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";


            // ================= FILTERED =================
            var filteredSql = @"
        SELECT COUNT(*) FROM
        (
            SELECT g.grade_id

            FROM txn_grades g

            JOIN mst_academic_classes ac 
                ON g.academic_class_id = ac.academic_class_id

            JOIN mst_classes c 
                ON ac.class_id = c.class_id

            JOIN mst_subjects s 
                ON g.subject_id = s.subject_id

            LEFT JOIN mst_rps r 
                ON r.academic_class_id = ac.academic_class_id 
               AND r.subject_id = g.subject_id

            JOIN mst_teachers t 
                ON g.teacher_id = t.teacher_id

            LEFT JOIN txn_grade_details d 
                ON d.grade_id = g.grade_id

            LEFT JOIN mst_detail_settings dt 
                ON g.grade_type = dt.detail_id 
               AND dt.header_id = 'GRADE_TYPE'

            WHERE (@classId IS NULL OR g.academic_class_id = @classId)
              AND (@academicYearId IS NULL OR ac.academic_year_id = @academicYearId)
              AND (@gradeType IS NULL OR g.grade_type = @gradeType)
              AND (@search IS NULL 
                   OR g.grade_type LIKE @search
                   OR dt.item_name LIKE @search
                   OR dt.item_desc LIKE @search)

            GROUP BY g.grade_id
        ) x";


            int recordsFiltered;

            using (var cmd = new SqlCommand(filteredSql, conn))
            {
                cmd.Parameters.AddWithValue("@classId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@academicYearId", (object)academic_year_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@gradeType", (object)grade_type ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@search", (object)searchKeyword ?? DBNull.Value);

                recordsFiltered = (int)cmd.ExecuteScalar();
            }


            // ================= DATA =================
            var list = new List<object>();

            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@classId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@academicYearId", (object)academic_year_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@gradeType", (object)grade_type ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@search", (object)searchKeyword ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);

                using var r = cmd.ExecuteReader();

                while (r.Read())
                {
                    list.Add(new
                    {
                        grade_id = r["grade_id"].ToString(),

                        grade_date = r["grade_date"] == DBNull.Value
                            ? null
                            : ((DateTime)r["grade_date"]).ToString("yyyy-MM-dd"),

                        start_date = r["start_date"],
                        end_date = r["end_date"],
                        semester = r["semester"]?.ToString(),

                        class_name = r["class_name"].ToString(),
                        subject_name = r["subject_name"].ToString(),
                        teacher_name = r["teacher_name"].ToString(),
                        grade_type = r["grade_type_desc"]?.ToString(),

                        minimum_value = Convert.ToDouble(r["minimum_value"]),

                        passed = Convert.ToInt32(r["passed"]),
                        remedial = Convert.ToInt32(r["remedial"])
                    });
                }
            }


            return Json(new
            {
                draw,
                recordsTotal,
                recordsFiltered,
                data = list
            });
        }

        [HttpGet]
        public IActionResult GetActiveAcademicYear()
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                var sql = @"
                    SELECT TOP 1 academic_year_id, start_date, end_date, semester
                    FROM mst_academic_years
                    WHERE status = 'ACTIVE'
                    ORDER BY start_date DESC";

                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();
                if (!rd.Read()) return Json(DTOResponse.ok(null));

                var startYear = Convert.ToDateTime(rd["start_date"]).Year;
                var endYear = Convert.ToDateTime(rd["end_date"]).Year;
                var semester = rd["semester"]?.ToString();

                return Json(DTOResponse.ok(new
                {
                    id = rd["academic_year_id"]?.ToString(),
                    text = $"{startYear} - {endYear} (Semester {semester})"
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
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
                g.grade_id,
                g.grade_date,
                g.academic_class_id,
                g.subject_id,
                g.teacher_id,
                g.grade_type,
                r.minimum_value,
                c.class_name,
                s.subject_name,
                CONCAT(t.first_name,' ',t.last_name) AS teacher_name,
                COALESCE(dt.item_desc, g.grade_type) AS grade_type_desc
            FROM txn_grades g
            LEFT JOIN mst_academic_classes ac ON g.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c ON ac.class_id = c.class_id
            LEFT JOIN mst_subjects s ON g.subject_id = s.subject_id
            LEFT JOIN mst_rps r ON r.academic_class_id = ac.academic_class_id AND r.subject_id = s.subject_id
            LEFT JOIN mst_teachers t ON g.teacher_id = t.teacher_id
            LEFT JOIN mst_detail_settings dt ON g.grade_type = dt.detail_id AND dt.header_id = 'GRADE_TYPE'
            WHERE g.grade_id = @id";

                dynamic header = null;
                using (var cmd = new SqlCommand(headerSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    if (r.Read())
                    {
                        header = new
                        {
                            grade_id = r["grade_id"].ToString(),
                            grade_date = r["grade_date"] == DBNull.Value ? null : ((DateTime)r["grade_date"]).ToString("yyyy-MM-dd"),
                            academic_class_id = r["academic_class_id"]?.ToString(),
                            subject_id = r["subject_id"]?.ToString(),
                            teacher_id = r["teacher_id"]?.ToString(),
                            grade_type = r["grade_type"]?.ToString(),
                            grade_type_desc = r["grade_type_desc"]?.ToString(),
                            minimum_value = r["minimum_value"].ToString(),
                            class_name = r["class_name"]?.ToString(),
                            subject_name = r["subject_name"]?.ToString(),
                            teacher_name = r["teacher_name"]?.ToString()
                        };
                    }
                    r.Close();
                }

                if (header == null) return Json(DTOResponse.fail("Not found", 404));

                var details = new List<object>();
                var detailsSql = @"
            SELECT 
                d.grade_detail_id,
                d.student_id,
                d.grade_value,
                d.notes,
                COALESCE(st.full_name, CONCAT(st.first_name,' ',st.last_name)) AS student_name,
                st.nis,
                st.profile_photo
            FROM txn_grade_details d
            LEFT JOIN mst_students st ON d.student_id = st.student_id
            WHERE d.grade_id = @id
            ORDER BY student_name";

                using (var cmd = new SqlCommand(detailsSql, conn))
                {
                    cmd.Parameters.AddWithValue("@id", id);
                    using var r = cmd.ExecuteReader();
                    while (r.Read())
                    {
                        details.Add(new
                        {
                            grade_detail_id = r["grade_detail_id"]?.ToString(),
                            student_id = r["student_id"]?.ToString(),
                            grade_value = r["grade_value"]?.ToString(),
                            notes = r["notes"]?.ToString(),
                            student_name = r["student_name"]?.ToString(),
                            nis = r["nis"]?.ToString(),
                            profile_photo = r["profile_photo"]?.ToString() ?? "/image/no-image.png"
                        });
                    }
                }

                return Json(DTOResponse.ok(new
                {
                    header.grade_id,
                    header.grade_date,
                    header.academic_class_id,
                    header.subject_id,
                    header.teacher_id,
                    header.grade_type,
                    header.grade_type_desc,
                    header.minimum_value,
                    header.class_name,
                    header.subject_name,
                    header.teacher_name,
                    details
                }));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpGet]
        public IActionResult GetSubjectMinValue(string academicClassId, string subjectId)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(subjectId)) return Json(DTOResponse.fail("Subject ID required", 400));

                using var conn = GetConn();
                conn.Open();

                using var cmd = new SqlCommand("SELECT minimum_value FROM mst_rps WHERE academic_class_id = @academicClassId AND subject_id = @subjectId", conn);
                cmd.Parameters.AddWithValue("@academicClassId", academicClassId);
                cmd.Parameters.AddWithValue("@subjectId", subjectId);
                var val = cmd.ExecuteScalar();

                double? minValue = val == DBNull.Value ? null : Convert.ToDouble(val);
                return Json(DTOResponse.ok(new { min_value = minValue }));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpPost]
        public IActionResult Create(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var classId = f["academic_class_id"].ToString();
                var subjectId = f["subject_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var gradeType = f["grade_type"].ToString();
                var gradeDate = f["grade_date"].ToString();

                if (string.IsNullOrWhiteSpace(classId) || string.IsNullOrWhiteSpace(subjectId) ||
                    string.IsNullOrWhiteSpace(teacherId) || string.IsNullOrWhiteSpace(gradeType))
                    return Json(DTOResponse.fail("Missing required fields", 400));

                var studentIds = JsonSerializer.Deserialize<List<string>>(f["student_ids"].ToString());
                var gradeValues = JsonSerializer.Deserialize<List<string>>(f["grade_values"].ToString());
                var notes = JsonSerializer.Deserialize<List<string>>(f["notes"].ToString());

                if (studentIds == null || studentIds.Count == 0 || gradeValues.Count != studentIds.Count || notes.Count != studentIds.Count)
                    return Json(DTOResponse.fail("Invalid details", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                var seqCmd = new SqlCommand("SELECT ISNULL(MAX(grade_id),'GRD0000') FROM txn_grades", conn, trx);
                var seq = int.Parse(seqCmd.ExecuteScalar().ToString().Substring(3)) + 1;
                var gradeId = "GRD" + seq.ToString("D4");

                var headerSql = @"
            INSERT INTO txn_grades (grade_id, subject_id, teacher_id, grade_type, academic_class_id, created_at, created_by, grade_date)
            VALUES (@id, @subj, @teacher, @type, @class, GETDATE(), @by, @date)";

                using (var cmd = new SqlCommand(headerSql, conn, trx))
                {
                    cmd.Parameters.AddWithValue("@id", gradeId);
                    cmd.Parameters.AddWithValue("@subj", subjectId);
                    cmd.Parameters.AddWithValue("@teacher", teacherId);
                    cmd.Parameters.AddWithValue("@type", gradeType);
                    cmd.Parameters.AddWithValue("@class", classId);
                    cmd.Parameters.AddWithValue("@by", DBNull.Value);
                    cmd.Parameters.AddWithValue("@date", gradeDate);
                    cmd.ExecuteNonQuery();
                }

                var detSeqCmd = new SqlCommand("SELECT ISNULL(MAX(grade_detail_id),'GRD0000') FROM txn_grade_details", conn, trx);
                var detSeq = int.Parse(detSeqCmd.ExecuteScalar().ToString().Substring(3));

                for (int i = 0; i < studentIds.Count; i++)
                {
                    detSeq++;
                    var detId = "GRD" + detSeq.ToString("D4");

                    var detSql = @"
                INSERT INTO txn_grade_details (grade_detail_id, grade_id, student_id, grade_value, notes, created_at, created_by)
                VALUES (@did, @gid, @sid, @val, @notes, GETDATE(), @by)";

                    using var dcmd = new SqlCommand(detSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@gid", gradeId);
                    dcmd.Parameters.AddWithValue("@sid", studentIds[i]);
                    dcmd.Parameters.AddWithValue("@val", (object)gradeValues[i] ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@notes", (object)notes[i] ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@by", DBNull.Value); // Fixed: set to NULL
                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "Grade created"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }

        [HttpPost]
        public IActionResult Update()
        {
            try
            {
                var f = Request.Form;
                var gradeId = f["grade_id"].ToString();
                var classId = f["academic_class_id"].ToString();
                var subjectId = f["subject_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var gradeType = f["grade_type"].ToString();
                var gradeDate = f["grade_date"].ToString();
                var rawDetails = f["details"].ToString();

                if (string.IsNullOrWhiteSpace(gradeId)) return Json(DTOResponse.fail("Invalid ID", 400));

                List<dynamic> details;
                using var json = JsonDocument.Parse(rawDetails);
                details = json.RootElement.EnumerateArray()
                    .Select(x => new
                    {
                        student_id = x.GetProperty("student_id").GetString(),
                        grade_value = x.TryGetProperty("grade_value", out var v) ? v.GetString() : null,
                        notes = x.TryGetProperty("notes", out var n) ? n.GetString() : null
                    })
                    .Where(d => !string.IsNullOrWhiteSpace(d.student_id))
                    .Cast<dynamic>()
                    .ToList();

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                var headerSql = @"
            UPDATE txn_grades SET subject_id = @subj, teacher_id = @teacher,
                grade_type = @type, academic_class_id = @class, updated_at = GETDATE(), grade_date = @date
            WHERE grade_id = @id";

                using (var cmd = new SqlCommand(headerSql, conn, trx))
                {
                    cmd.Parameters.AddWithValue("@id", gradeId);
                    cmd.Parameters.AddWithValue("@subj", subjectId);
                    cmd.Parameters.AddWithValue("@teacher", teacherId);
                    cmd.Parameters.AddWithValue("@type", gradeType);
                    cmd.Parameters.AddWithValue("@class", classId);
                    cmd.Parameters.AddWithValue("@date", gradeDate);
                    cmd.ExecuteNonQuery();
                }

                new SqlCommand("DELETE FROM txn_grade_details WHERE grade_id = @id", conn, trx)
                { Parameters = { new SqlParameter("@id", gradeId) } }.ExecuteNonQuery();

                var detSeqCmd = new SqlCommand("SELECT ISNULL(MAX(grade_detail_id),'GDT0000') FROM txn_grade_details", conn, trx);
                var detSeq = int.Parse(detSeqCmd.ExecuteScalar().ToString().Substring(3));

                foreach (var d in details)
                {
                    detSeq++;
                    var detId = "GDT" + detSeq.ToString("D4");

                    var detSql = @"
                INSERT INTO txn_grade_details (grade_detail_id, grade_id, student_id, grade_value, notes, created_at, updated_at, created_by)
                VALUES (@did, @gid, @sid, @val, @notes, GETDATE(), GETDATE(), @by)";

                    using var dcmd = new SqlCommand(detSql, conn, trx);
                    dcmd.Parameters.AddWithValue("@did", detId);
                    dcmd.Parameters.AddWithValue("@gid", gradeId);
                    dcmd.Parameters.AddWithValue("@sid", d.student_id);
                    dcmd.Parameters.AddWithValue("@val", (object)d.grade_value ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@notes", (object)d.notes ?? DBNull.Value);
                    dcmd.Parameters.AddWithValue("@by", DBNull.Value); // Added for consistency (NULL)
                    dcmd.ExecuteNonQuery();
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "Grade updated"));
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

                new SqlCommand("DELETE FROM txn_grade_details WHERE grade_id = @id", conn)
                { Parameters = { new SqlParameter("@id", req.id) } }.ExecuteNonQuery();

                new SqlCommand("DELETE FROM txn_grades WHERE grade_id = @id", conn)
                { Parameters = { new SqlParameter("@id", req.id) } }.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "Deleted"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }
    }
}
