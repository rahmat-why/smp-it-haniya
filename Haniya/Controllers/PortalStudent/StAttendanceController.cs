using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using System.Security.Claims;
using Haniya.Models;

namespace Haniya.Controllers.PortalStudent
{
    [Authorize]
    public class StAttendanceController : Controller
    {
        private readonly IConfiguration _config;

        public StAttendanceController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        // View
        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StAttendance/Index.cshtml");
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
        public IActionResult GetMyAttendance([FromBody] ListRequest? req)
        {
            try
            {
                req ??= new ListRequest();
                var page = req.page <= 0 ? 1 : req.page;
                var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);
                var offset = (page - 1) * limit;
                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var search);

                // Ambil student_id dari Claims Login
                var studentId = User.FindFirst("StudentId")?.Value;
                if (string.IsNullOrWhiteSpace(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));

                using var conn = GetConn();
                conn.Open();

                var searchPattern = string.IsNullOrWhiteSpace(search) ? null : $"%{search.Trim()}%";
                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["date"] = "a.attendance_date",
                    ["class"] = "cl.class_name",
                    ["teacher"] = "t.full_name",
                    ["status"] = "d.status",
                    ["notes"] = "d.notes"
                };
                var sort = req.sort ?? new ListSort();
                var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "a.attendance_date";
                var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";
                var secondaryOrder = string.Equals(orderBy, "a.attendance_date", StringComparison.OrdinalIgnoreCase)
                    ? "a.attendance_id DESC"
                    : "a.attendance_date DESC";

                var whereSql = @"
                    WHERE d.student_id = @student_id
                      AND (
                            @search IS NULL
                            OR cl.class_name LIKE @search
                            OR t.full_name LIKE @search
                            OR d.status LIKE @search
                            OR d.notes LIKE @search
                          )";

                var totalSql = @"
                    SELECT COUNT(*)
                    FROM txn_attendances a
                    JOIN txn_attendance_details d ON a.attendance_id = d.attendance_id
                    JOIN mst_academic_classes c ON a.academic_class_id = c.academic_class_id
                    JOIN mst_classes cl ON cl.class_id = c.class_id
                    JOIN mst_teachers t ON a.teacher_id = t.teacher_id
                    WHERE d.student_id = @student_id";

                var filteredSql = @"
                    SELECT COUNT(*)
                    FROM txn_attendances a
                    JOIN txn_attendance_details d ON a.attendance_id = d.attendance_id
                    JOIN mst_academic_classes c ON a.academic_class_id = c.academic_class_id
                    JOIN mst_classes cl ON cl.class_id = c.class_id
                    JOIN mst_teachers t ON a.teacher_id = t.teacher_id
                    " + whereSql;

                int recordsTotal;
                using (var totalCmd = new SqlCommand(totalSql, conn))
                {
                    totalCmd.Parameters.AddWithValue("@student_id", studentId);
                    recordsTotal = Convert.ToInt32(totalCmd.ExecuteScalar() ?? 0);
                }

                int recordsFiltered;
                using (var filteredCmd = new SqlCommand(filteredSql, conn))
                {
                    filteredCmd.Parameters.AddWithValue("@student_id", studentId);
                    filteredCmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                    recordsFiltered = Convert.ToInt32(filteredCmd.ExecuteScalar() ?? 0);
                }

                var sql = @"
                    SELECT
                        a.attendance_id,
                        a.attendance_date,

                        d.status,
                        d.notes,

                        cl.class_name,
                        t.full_name AS teacher_name

                    FROM txn_attendances a

                    JOIN txn_attendance_details d
                        ON a.attendance_id = d.attendance_id

                    JOIN mst_academic_classes c
                        ON a.academic_class_id = c.academic_class_id

                    JOIN mst_classes cl
                        ON cl.class_id = c.class_id

                    JOIN mst_teachers t
                        ON a.teacher_id = t.teacher_id

                    " + whereSql + @"
                    ORDER BY " + orderBy + " " + orderDir + @", " + secondaryOrder + @"
                    OFFSET @start ROWS FETCH NEXT @length ROWS ONLY
                ";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@student_id", studentId);
                cmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@start", offset);
                cmd.Parameters.AddWithValue("@length", limit);

                using var rd = cmd.ExecuteReader();

                var list = new List<object>();

                while (rd.Read())
                {
                    list.Add(new
                    {
                        date = Convert.ToDateTime(rd["attendance_date"])
                                    .ToString("yyyy-MM-dd"),

                        status = rd["status"]?.ToString(),
                        notes = rd["notes"]?.ToString(),
                        class_name = rd["class_name"]?.ToString(),
                        teacher = rd["teacher_name"]?.ToString()
                    });
                }

                var hasNextPage = (offset + list.Count) < recordsFiltered;
                return Json(DTOResponse.ok(new
                {
                    data = list,
                    hasNextPage,
                    totalRows = recordsFiltered,
                    totalAll = recordsTotal
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
