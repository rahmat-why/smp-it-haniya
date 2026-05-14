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

        private (int draw, int start, int length, string searchValue, int orderColumnIndex, string orderDir) ParseDataTablesQuery()
        {
            var form = Request.HasFormContentType ? Request.Form : null;
            var q = Request.Query;

            string GetVal(string key)
            {
                if (form != null && form.ContainsKey(key)) return form[key].ToString();
                return q[key].ToString();
            }

            int.TryParse(GetVal("draw"), out var draw);
            if (draw <= 0) draw = 1;
            int.TryParse(GetVal("start"), out var start);
            if (start < 0) start = 0;
            int.TryParse(GetVal("length"), out var length);
            if (length <= 0) length = 10;
            var searchValue = GetVal("search[value]") ?? string.Empty;
            int.TryParse(GetVal("order[0][column]"), out var orderColumnIndex);
            var rawDir = (GetVal("order[0][dir]") ?? "").ToUpper();
            var orderDir = rawDir is "ASC" or "DESC" ? rawDir : "DESC";

            return (draw, start, length, searchValue, orderColumnIndex, orderDir);
        }

        private string GetAttendanceOrderByColumn(int orderColumnIndex)
        {
            return orderColumnIndex switch
            {
                0 => "a.attendance_date",
                1 => "cl.class_name",
                2 => "t.full_name",
                3 => "d.status",
                4 => "d.notes",
                _ => "a.attendance_date"
            };
        }

        [HttpPost]
        public IActionResult GetMyAttendance()
        {
            try
            {
                var (draw, start, length, search, orderColumnIndex, orderDir) = ParseDataTablesQuery();

                // Ambil student_id dari Claims Login
                var studentId = User.FindFirst("StudentId")?.Value;
                if (string.IsNullOrWhiteSpace(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));

                using var conn = GetConn();
                conn.Open();

                var searchPattern = string.IsNullOrWhiteSpace(search) ? null : $"%{search.Trim()}%";
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

                var orderBy = GetAttendanceOrderByColumn(orderColumnIndex);
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
                    ORDER BY " + orderBy + " " + orderDir + @", a.attendance_date DESC
                    OFFSET @start ROWS FETCH NEXT @length ROWS ONLY
                ";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@student_id", studentId);
                cmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);

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

                return Json(new { draw, recordsTotal, recordsFiltered, data = list });
            }
            catch (Exception ex)
            {
                return Json(new
                {
                    success = false,
                    message = ex.Message
                });
            }
        }
    }
}
