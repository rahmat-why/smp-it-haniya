using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;


namespace Haniya.Controllers.LayoutLandingPage
{
    public class LayoutController : Controller
    {
        private readonly IConfiguration _config;

        public LayoutController(IConfiguration config)
        {
            _config = config;
        }

        [HttpGet]
        public JsonResult GetLayoutData()
        {
            var data = new Dictionary<string, string>();
            string connString = _config.GetConnectionString("DefaultConnection");

            using (SqlConnection conn = new SqlConnection(connString))
            {
                conn.Open();

                string query = @"
                SELECT detail_id, item_name, item_desc
                FROM mst_detail_setting_landingpages
                WHERE header_id = 'CONTACT'
                AND status = 'ACTIVE'
            ";

                using (SqlCommand cmd = new SqlCommand(query, conn))
                using (SqlDataReader reader = cmd.ExecuteReader())
                {
                    while (reader.Read())
                    {
                        string key = reader["detail_id"].ToString();
                        data[key + "_NAME"] = reader["item_name"]?.ToString();
                        data[key + "_DESC"] = reader["item_desc"]?.ToString();
                    }
                }
            }

            return Json(data);
        }

    }
}
