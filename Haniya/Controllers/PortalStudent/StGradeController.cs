using Haniya.Models;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;

namespace Haniya.Controllers.PortalStudent
{
    [Authorize]
    public class StGradeController : Controller
    {
        private readonly IConfiguration _config;
        public StGradeController(IConfiguration config) => _config = config;

        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StGrade/Index.cshtml");
        }

        public class ListSort
        {
            public string field { get; set; } = "date";
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
        public IActionResult GetMyGrade([FromBody] ListRequest? req)
        {
            try
            {
                var studentId = User?.FindFirst("StudentId")?.Value;
                if (string.IsNullOrWhiteSpace(studentId))
                    return Json(DTOResponse.fail("Invalid student ID", 400));

                req ??= new ListRequest();
                var page = req.page <= 0 ? 1 : req.page;
                var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);
                var offset = (page - 1) * limit;
                var take = limit + 1;

                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var search);

                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["date"] = "g.grade_date",
                    ["class"] = "c.class_name",
                    ["subject"] = "s.subject_name",
                    ["teacher"] = "t.first_name",
                    ["type"] = "dt.item_desc",
                    ["min"] = "s.minimum_value",
                    ["score"] = "TRY_CONVERT(decimal(10,2), d.grade_value)"
                };
                var sort = req.sort ?? new ListSort();
                var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "g.grade_date";
                var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";

                using var conn = GetConn();
                conn.Open();

                var where = new List<string> { "d.student_id = @studentId" };
                if (!string.IsNullOrWhiteSpace(search))
                {
                    where.Add(@"(
                        c.class_name LIKE @search
                        OR s.subject_name LIKE @search
                        OR CONCAT(t.first_name,' ',t.last_name) LIKE @search
                        OR COALESCE(dt.item_desc, g.grade_type) LIKE @search
                        OR d.notes LIKE @search
                        OR d.grade_value LIKE @search
                    )");
                }
                var whereSql = "WHERE " + string.Join(" AND ", where);

                var sql = @"
            SELECT
                g.grade_id,
                g.grade_date,
                c.class_name,
                s.subject_name,
                CONCAT(t.first_name,' ',t.last_name) AS teacher_name,
                COALESCE(dt.item_desc, g.grade_type) AS grade_type_desc,
                s.minimum_value,
                d.grade_value,
                d.notes,
                d.grade_detail_id
            FROM txn_grade_details d
            JOIN txn_grades g ON d.grade_id = g.grade_id
            LEFT JOIN mst_academic_classes ac ON g.academic_class_id = ac.academic_class_id
                LEFT JOIN mst_classes c ON ac.class_id = c.class_id
                LEFT JOIN mst_subjects s ON g.subject_id = s.subject_id
                LEFT JOIN mst_teachers t ON g.teacher_id = t.teacher_id
                LEFT JOIN mst_detail_settings dt ON g.grade_type = dt.detail_id AND dt.header_id = 'GRADE_TYPE'
            {WHERE_SQL}
            ORDER BY {ORDER_BY} {ORDER_DIR}, d.created_at DESC
            OFFSET @offset ROWS FETCH NEXT @take ROWS ONLY"
                    .Replace("{WHERE_SQL}", whereSql)
                    .Replace("{ORDER_BY}", orderBy)
                    .Replace("{ORDER_DIR}", orderDir);

                var list = new List<object>();

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", studentId);
                    cmd.Parameters.AddWithValue("@offset", offset);
                    cmd.Parameters.AddWithValue("@take", take);
                    if (!string.IsNullOrWhiteSpace(search))
                        cmd.Parameters.AddWithValue("@search", $"%{search.Trim()}%");

                    using var r = cmd.ExecuteReader();
                    while (r.Read())
                    {
                        list.Add(new
                        {
                            grade_id = r["grade_id"].ToString(),
                            grade_date = r["grade_date"] == DBNull.Value ? null : ((DateTime)r["grade_date"]).ToString("yyyy-MM-dd"),
                            class_name = r["class_name"]?.ToString(),
                            subject_name = r["subject_name"]?.ToString(),
                            teacher_name = r["teacher_name"]?.ToString(),
                            grade_type_desc = r["grade_type_desc"]?.ToString(),
                            minimum_value = r["minimum_value"] as double?,
                            grade_value = r["grade_value"]?.ToString(),
                            notes = r["notes"]?.ToString(),
                            grade_detail_id = r["grade_detail_id"]?.ToString()
                        });
                    }
                }

                var hasNextPage = list.Count > limit;
                if (hasNextPage) list = list.Take(limit).ToList();

                return Json(DTOResponse.ok(new { data = list, hasNextPage }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
