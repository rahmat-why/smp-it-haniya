using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Haniya.Models;

namespace Haniya.Controllers.PortalAdmin
{
    public class MenuController : Controller
    {
        private readonly IConfiguration _config;

        public MenuController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        [HttpGet]
        public IActionResult GetMenus()
        {
            try
            {
                // Get user role from claims
                var userType = User.FindFirst("UserType")?.Value;
                var userLevel = User.FindFirst("Level")?.Value;

                // Determine role_id based on user type
                string roleId = GetRoleId(userType, userLevel);

                if (string.IsNullOrEmpty(roleId))
                {
                    return Json(DTOResponse.fail("User role not found", 401));
                }

                var menus = new List<object>();

                using (var conn = GetConn())
                {
                    conn.Open();

                    var sql = @"
                        SELECT DISTINCT
                            m.menu_id,
                            m.parent_id,
                            m.menu_name,
                            m.url,
                            m.icon,
                            m.sort_order
                        FROM mst_menu m
                        INNER JOIN txn_menu_role mr ON m.menu_id = mr.menu_id
                        WHERE m.is_active = 1
                        AND mr.role_id = @roleId
                        AND mr.is_view = 1
                        ORDER BY m.sort_order";

                    using var cmd = new SqlCommand(sql, conn);
                    cmd.Parameters.AddWithValue("@roleId", roleId);

                    using var rd = cmd.ExecuteReader();
                    while (rd.Read())
                    {
                        menus.Add(new
                        {
                            menu_id = rd["menu_id"].ToString(),
                            parent_id = rd["parent_id"] == DBNull.Value ? null : rd["parent_id"].ToString(),
                            menu_name = rd["menu_name"].ToString(),
                            url = rd["url"].ToString(),
                            icon = rd["icon"]?.ToString(),
                            sort_order = rd["sort_order"]
                        });
                    }
                }

                return Json(DTOResponse.ok(menus));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        // Helper method to map UserType to role_id
        private string GetRoleId(string userType, string userLevel)
        {
            // Map based on your database role_id values
            // Adjust these values according to your actual role_id in the database

            if (userType == "Employee")
            {
                // You can further differentiate by level if needed
                return "ADMIN"; // or use userLevel if it matches role_id
            }
            else if (userType == "Teacher")
            {
                return "TEACHER";
            }
            else if (userType == "Student")
            {
                return "STUDENT";
            }

            return null;
        }
    }
}