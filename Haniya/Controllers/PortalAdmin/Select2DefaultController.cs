using Haniya.Models;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;

namespace Haniya.Controllers.PortalAdmin
{
    public class Select2DefaultController : Controller
    {
        private readonly IConfiguration _config;

        public Select2DefaultController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        [HttpGet]
        public IActionResult ActiveAcademicYear()
        {
            try
            {
                using var conn = GetConn();
                conn.Open();
                var sql = @"
            SELECT TOP 1 academic_year_id, start_date, end_date, semester, status
            FROM mst_academic_years
            WHERE status = 'ACTIVE'
            ORDER BY start_date DESC";
                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();
                if (rd.Read())
                {
                    var startYear = Convert.ToDateTime(rd["start_date"]).Year;
                    var endYear = Convert.ToDateTime(rd["end_date"]).Year;
                    return Json(new
                    {
                        id = rd["academic_year_id"]?.ToString(),
                        text = $"{startYear} - {endYear} (Semester {rd["semester"]?.ToString() ?? "?"})",
                        start_date = rd["start_date"]?.ToString(),
                        end_date = rd["end_date"]?.ToString(),
                        status = rd["status"]?.ToString()
                    });
                }
                return Json(null);
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
