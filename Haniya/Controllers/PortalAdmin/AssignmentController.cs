using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Haniya.Models;
using System.Data;
using System.Security.Claims;

namespace Haniya.Controllers.PortalAdmin
{
    [Authorize]
    public class AssignmentController : Controller
    {
        private readonly IConfiguration _config;
        public AssignmentController(IConfiguration config) => _config = config;

        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        private bool IsTeacherUser() => string.Equals(User.FindFirst("UserType")?.Value, "Teacher", StringComparison.OrdinalIgnoreCase);

        [HttpGet]
        public IActionResult Index()
        {
            if (!IsTeacherUser()) return RedirectToAction("AccessDenied", "LoginAdmin");
            return View("~/Views/PortalAdmin/Assignment/Index.cshtml");
        }

        [HttpGet]
        public IActionResult Create()
        {
            if (!IsTeacherUser()) return RedirectToAction("AccessDenied", "LoginAdmin");
            return View("~/Views/PortalAdmin/Assignment/Create.cshtml");
        }



        public class ListSort
        {
            public string field { get; set; } = "created_at";
            public string order { get; set; } = "desc";
        }

        public class ListRequest
        {
            public int page { get; set; } = 1;
            public int limit { get; set; } = 10;
            public Dictionary<string, string>? filters { get; set; }
            public ListSort? sort { get; set; }
        }

        [HttpPost]
        public IActionResult GetAll([FromBody] ListRequest? req)
        {
            if (!IsTeacherUser()) return Json(DTOResponse.fail("Forbidden", 403));

            req ??= new ListRequest();
            var page = req.page <= 0 ? 1 : req.page;
            var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);

            var filters = req.filters ?? new Dictionary<string, string>();
            filters.TryGetValue("search", out var searchValue);
            filters.TryGetValue("academic_year_id", out var academic_year_id);
            filters.TryGetValue("academic_class_id", out var academic_class_id);
            filters.TryGetValue("teacher_id", out var teacher_id);

            var userType = User.FindFirst("UserType")?.Value;
            var loggedTeacherId = User.FindFirst("TeacherId")?.Value;

            if (userType == "Teacher" && !string.IsNullOrEmpty(loggedTeacherId))
            {
                teacher_id = loggedTeacherId;
            }

            academic_year_id = string.IsNullOrWhiteSpace(academic_year_id) ? null : academic_year_id;
            academic_class_id = string.IsNullOrWhiteSpace(academic_class_id) ? null : academic_class_id;
            teacher_id = string.IsNullOrWhiteSpace(teacher_id) ? null : teacher_id;
            searchValue = string.IsNullOrWhiteSpace(searchValue) ? null : searchValue.Trim();

            var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
            {
                ["created_at"] = "a.created_at",
                ["title"] = "a.title",
                ["due_date"] = "a.due_date",
                ["class"] = "c.class_name",
                ["teacher"] = "t.first_name",
                ["subject"] = "s.subject_name"
            };
            var sort = req.sort ?? new ListSort();
            var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "a.created_at";
            var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";

            using var conn = GetConn();
            conn.Open();

            var where = @"
          AND (@academicYearId IS NULL OR ac.academic_year_id = @academicYearId)
          AND (@academicClassId IS NULL OR a.academic_class_id = @academicClassId)
          AND (@teacherId IS NULL OR a.teacher_id = @teacherId)";
            if (!string.IsNullOrWhiteSpace(searchValue))
            {
                where += @"
          AND (
                a.title LIKE @search
                OR CONCAT(t.first_name, ' ', t.last_name) LIKE @search
                OR c.class_name LIKE @search
                OR s.subject_name LIKE @search
          )";
            }

            var totalSql = @"
        SELECT COUNT(*)
        FROM txn_assignments a
        LEFT JOIN mst_teachers t ON a.teacher_id = t.teacher_id
        LEFT JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
        LEFT JOIN mst_classes c ON ac.class_id = c.class_id
        LEFT JOIN mst_subjects s ON a.subject_id = s.subject_id
        WHERE a.status = 'ACTIVE'
        " + where;

            var totalRows = 0;
            using (var cmd = new SqlCommand(totalSql, conn))
            {
                cmd.Parameters.AddWithValue("@academicYearId", (object)academic_year_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@academicClassId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@teacherId", (object)teacher_id ?? DBNull.Value);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    cmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                totalRows = (int)cmd.ExecuteScalar();
            }

            var totalPages = totalRows == 0 ? 1 : (int)Math.Ceiling(totalRows / (double)limit);
            page = Math.Min(page, totalPages);
            var offset = (page - 1) * limit;

            var sql = @"
        SELECT 
            a.assignment_id,
            a.title,
            a.due_date,
            a.created_at,
            NULLIF(LTRIM(RTRIM(CONCAT(t.first_name, ' ', t.last_name))), '') AS teacher_name,
            c.class_name,
            s.subject_name
        FROM txn_assignments a
        LEFT JOIN mst_teachers t ON a.teacher_id = t.teacher_id
        LEFT JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
        LEFT JOIN mst_classes c ON ac.class_id = c.class_id
        LEFT JOIN mst_subjects s ON a.subject_id = s.subject_id
        WHERE a.status = 'ACTIVE'
        " + where + @"
        ORDER BY {ORDER_BY} {ORDER_DIR}
        OFFSET @offset ROWS
        FETCH NEXT @limit ROWS ONLY"
            .Replace("{ORDER_BY}", orderBy)
            .Replace("{ORDER_DIR}", orderDir);

            var list = new List<object>();

            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@academicYearId", (object)academic_year_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@academicClassId", (object)academic_class_id ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@teacherId", (object)teacher_id ?? DBNull.Value);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    cmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                cmd.Parameters.Add("@offset", SqlDbType.Int).Value = offset;
                cmd.Parameters.Add("@limit", SqlDbType.Int).Value = limit;

                using var r = cmd.ExecuteReader();

                while (r.Read())
                {
                    list.Add(new
                    {
                        assignment_id = r["assignment_id"].ToString(),
                        title = r["title"].ToString(),
                        teacher_name = r["teacher_name"].ToString(),
                        class_name = r["class_name"].ToString(),
                        subject_name = r["subject_name"].ToString(),
                        due_date = r["due_date"] != DBNull.Value ? Convert.ToDateTime(r["due_date"]).ToString("yyyy-MM-dd") : null,
                        created_at = r["created_at"] != DBNull.Value ? Convert.ToDateTime(r["created_at"]).ToString("yyyy-MM-dd HH:mm") : null
                    });
                }
            }

            var hasNextPage = (offset + list.Count) < totalRows;
            return Json(DTOResponse.ok(new { data = list, hasNextPage, totalRows, currentPage = page, limit }));
        }

        [HttpGet]
        public IActionResult GetById(string id)
        {
            try
            {
                if (!IsTeacherUser()) return Json(DTOResponse.fail("Forbidden", 403));

                if (string.IsNullOrWhiteSpace(id))
                    return Json(DTOResponse.fail("Invalid ID", 400));

                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT 
                a.assignment_id,
                a.title,
                a.description,
                a.due_date,
                a.academic_class_id,
                ac.academic_year_id,
                a.teacher_id,
                a.subject_id
            FROM txn_assignments a
            LEFT JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
            WHERE a.assignment_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);
                using var r = cmd.ExecuteReader();

                if (r.Read())
                {
                    return Json(DTOResponse.ok(new
                    {
                        assignment_id = r["assignment_id"].ToString(),
                        title = r["title"].ToString(),
                        description = r["description"].ToString(),
                        due_date = r["due_date"] != DBNull.Value ? Convert.ToDateTime(r["due_date"]).ToString("yyyy-MM-dd") : null,
                        academic_class_id = r["academic_class_id"].ToString(),
                        academic_year_id = r["academic_year_id"].ToString(),
                        teacher_id = r["teacher_id"].ToString(),
                        subject_id = r["subject_id"].ToString()
                    }));
                }

                return Json(DTOResponse.fail("Not found", 404));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Create(IFormCollection form)
        {
            try
            {
                if (!IsTeacherUser()) return Json(DTOResponse.fail("Forbidden", 403));

                var f = Request.Form;
                var title = f["title"].ToString();
                var description = f["description"].ToString();
                var dueDate = f["due_date"].ToString();
                var academicClassId = f["academic_class_id"].ToString();
                var teacherId = f["teacher_id"].ToString();
                var subjectId = f["subject_id"].ToString();

                var userType = User.FindFirst("UserType")?.Value;
                var loggedTeacherId = User.FindFirst("TeacherId")?.Value;

                if (userType == "Teacher" && !string.IsNullOrEmpty(loggedTeacherId))
                {
                    teacherId = loggedTeacherId;
                }

                if (string.IsNullOrWhiteSpace(title) || string.IsNullOrWhiteSpace(academicClassId) || string.IsNullOrWhiteSpace(teacherId) || string.IsNullOrWhiteSpace(subjectId))
                    return Json(DTOResponse.fail("Title, Class, Subject, and Teacher are required", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                var seqCmd = new SqlCommand("SELECT ISNULL(MAX(CAST(SUBSTRING(assignment_id, 4, 10) AS INT)), 0) FROM txn_assignments WHERE assignment_id LIKE 'TSK%'", conn, trx);
                var maxSeq = seqCmd.ExecuteScalar();
                int seq = maxSeq != DBNull.Value ? Convert.ToInt32(maxSeq) + 1 : 1;
                var assignmentId = "TSK" + seq.ToString("D5");

                var sql = @"
                    INSERT INTO txn_assignments (
                        assignment_id, academic_class_id, teacher_id, subject_id, title, description, due_date, status, created_at
                    )
                    VALUES (
                        @id, @academicClassId, @teacherId, @subjectId, @title, @description, @dueDate, 'ACTIVE', GETDATE()
                    )";

                using var cmd = new SqlCommand(sql, conn, trx);
                cmd.Parameters.AddWithValue("@id", assignmentId);
                cmd.Parameters.AddWithValue("@academicClassId", academicClassId);
                cmd.Parameters.AddWithValue("@teacherId", teacherId);
                cmd.Parameters.AddWithValue("@subjectId", subjectId);
                cmd.Parameters.AddWithValue("@title", title);
                cmd.Parameters.AddWithValue("@description", string.IsNullOrWhiteSpace(description) ? DBNull.Value : (object)description);
                cmd.Parameters.AddWithValue("@dueDate", string.IsNullOrWhiteSpace(dueDate) ? DBNull.Value : (object)Convert.ToDateTime(dueDate));
                cmd.ExecuteNonQuery();

                // Generate reminders for all students in the selected academic class
                var studentsCmd = new SqlCommand("SELECT student_id FROM mst_student_classes WHERE academic_class_id = @academicClassId", conn, trx);
                studentsCmd.Parameters.AddWithValue("@academicClassId", academicClassId);
                var studentIds = new List<string>();
                using (var r = studentsCmd.ExecuteReader())
                {
                    while (r.Read())
                    {
                        studentIds.Add(r["student_id"].ToString());
                    }
                }

                if (studentIds.Count > 0)
                {
                    var remSeqCmd = new SqlCommand("SELECT ISNULL(MAX(CAST(SUBSTRING(reminder_id, 4, 10) AS INT)), 0) FROM txn_assignment_reminders WHERE reminder_id LIKE 'REM%'", conn, trx);
                    var maxRemSeq = remSeqCmd.ExecuteScalar();
                    int remSeq = maxRemSeq != DBNull.Value ? Convert.ToInt32(maxRemSeq) : 0;

                    foreach (var sId in studentIds)
                    {
                        remSeq++;
                        var reminderId = "REM" + remSeq.ToString("D5");
                        var remSql = @"
                            INSERT INTO txn_assignment_reminders (reminder_id, assignment_id, student_id, is_read)
                            VALUES (@rid, @aid, @sid, 0)";
                        using var rCmd = new SqlCommand(remSql, conn, trx);
                        rCmd.Parameters.AddWithValue("@rid", reminderId);
                        rCmd.Parameters.AddWithValue("@aid", assignmentId);
                        rCmd.Parameters.AddWithValue("@sid", sId);
                        rCmd.ExecuteNonQuery();
                    }
                }

                trx.Commit();
                return Json(DTOResponse.ok(null, "Assignment created successfully"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }



        [HttpPost]
        public IActionResult Delete([FromBody] DTORequest req)
        {
            try
            {
                if (!IsTeacherUser()) return Json(DTOResponse.fail("Forbidden", 403));

                if (string.IsNullOrEmpty(req?.id)) return Json(DTOResponse.fail("Invalid ID", 400));

                using var conn = GetConn();
                conn.Open();
                using var trx = conn.BeginTransaction();

                new SqlCommand("DELETE FROM txn_assignment_reminders WHERE assignment_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", req.id) }
                }.ExecuteNonQuery();

                new SqlCommand("DELETE FROM txn_assignments WHERE assignment_id = @id", conn, trx)
                {
                    Parameters = { new SqlParameter("@id", req.id) }
                }.ExecuteNonQuery();

                trx.Commit();
                return Json(DTOResponse.ok(null, "Assignment deleted"));
            }
            catch (Exception ex) { return Json(DTOResponse.fail(ex.Message, 500)); }
        }
    }
}
