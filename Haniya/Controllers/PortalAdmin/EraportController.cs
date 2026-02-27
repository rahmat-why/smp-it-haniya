using Haniya.Models;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;


namespace Haniya.Controllers.PortalAdmin
{
    public class EraportController : Controller
    {
        private readonly IConfiguration _config;

        public EraportController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/E-Raport/Index.cshtml");
        }

        private (int draw, int start, int length, string searchValue, string orderColumn, string orderDir)
            ParseDataTablesQuery(string[] columns)
        {
            var q = Request.Query;

            int.TryParse(q["draw"], out var draw);
            if (draw <= 0) draw = 1;

            int.TryParse(q["start"], out var start);
            if (start < 0) start = 0;

            int.TryParse(q["length"], out var length);
            if (length <= 0) length = 10;

            var searchValue = q["search[value]"].ToString() ?? string.Empty;

            var orderColumn = "full_name";
            var orderDir = "DESC";

            var orderColIdxStr = q["order[0][column]"].ToString();
            if (int.TryParse(orderColIdxStr, out var orderColIdx))
            {
                if (orderColIdx >= 0 && orderColIdx < columns.Length)
                    orderColumn = columns[orderColIdx];
            }

            var dir = q["order[0][dir]"].ToString();
            if (!string.IsNullOrWhiteSpace(dir) &&
                (dir.Equals("asc", StringComparison.OrdinalIgnoreCase) ||
                 dir.Equals("desc", StringComparison.OrdinalIgnoreCase)))
            {
                orderDir = dir.ToUpper();
            }

            return (draw, start, length, searchValue, orderColumn, orderDir);
        }

        [HttpGet]
        public IActionResult GetAll()
        {
            try
            {
                var columns = new[]
                {
                    "s.full_name",
                    "c.class_name"
                };

                var (draw, start, length, searchValue, orderColumn, orderDir) =
                    ParseDataTablesQuery(columns);

                using var conn = GetConn();
                conn.Open();

                string baseQuery = @"
                    FROM mst_students s
                    LEFT JOIN mst_student_classes sc ON sc.student_id = s.student_id
                    LEFT JOIN mst_academic_classes ac ON ac.academic_class_id = sc.academic_class_id
                    LEFT JOIN mst_classes c ON c.class_id = ac.class_id
                    WHERE s.status = 'ACTIVE'
                ";

                string whereSearch = "";
                if (!string.IsNullOrWhiteSpace(searchValue))
                {
                    whereSearch = @"
                    AND (
                        s.nis LIKE @search OR
                        s.full_name LIKE @search OR
                        c.class_name LIKE @search
                    )";
                }

                // TOTAL
                var totalCmd = new SqlCommand(
                    "SELECT COUNT(DISTINCT s.student_id) " + baseQuery,
                    conn
                );
                var recordsTotal = (int)totalCmd.ExecuteScalar();

                // FILTERED
                var filteredCmd = new SqlCommand(
                    "SELECT COUNT(DISTINCT s.student_id) " + baseQuery + whereSearch,
                    conn
                );

                if (!string.IsNullOrWhiteSpace(searchValue))
                    filteredCmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                var recordsFiltered = (int)filteredCmd.ExecuteScalar();

                // DATA
                var sql = $@"
                    SELECT 
                        s.student_id,
                        s.nis,
                        s.full_name,
                        s.profile_photo,
                        c.class_name
                    {baseQuery}
                    {whereSearch}
                    ORDER BY {orderColumn} {orderDir}
                    OFFSET @start ROWS FETCH NEXT @length ROWS ONLY
                ";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);

                if (!string.IsNullOrWhiteSpace(searchValue))
                    cmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    list.Add(new
                    {
                        student_id = rd["student_id"],
                        nis = rd["nis"],
                        full_name = rd["full_name"],
                        profile_photo = rd["profile_photo"],
                        class_name = rd["class_name"] ?? "-"
                    });
                }

                return Json(new
                {
                    draw,
                    recordsTotal,
                    recordsFiltered,
                    data = list
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
