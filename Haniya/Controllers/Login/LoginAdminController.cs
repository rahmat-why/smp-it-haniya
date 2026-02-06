using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using System.Security.Claims;

namespace Haniya.Controllers.Login
{
    public class LoginAdminController : Controller
    {
        private readonly IConfiguration _config;

        public LoginAdminController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        [HttpGet]
        public IActionResult Index()
        {
            // If already logged in, redirect to dashboard
            if (User.Identity.IsAuthenticated && User.HasClaim("UserType", "Employee"))
            {
                return RedirectToAction("Admin", "Dashboard");
            }

            return View("~/Views/Login/LoginAdmin.cshtml");
        }

        [HttpPost]
        public IActionResult Login()
        {
            try
            {
                var username = Request.Form["username"].ToString();
                var password = Request.Form["password"].ToString();
                var rememberMe = Request.Form["remember_me"].ToString() == "true";

                if (string.IsNullOrEmpty(username) || string.IsNullOrEmpty(password))
                {
                    return Json(new { success = false, message = "Username and password are required" });
                }

                using var conn = GetConn();
                conn.Open();

                var sql = @"SELECT 
                            employee_id, 
                            first_name, 
                            last_name, 
                            username, 
                            password, 
                            level, 
                            status,
                            profile_photo
                        FROM mst_employees 
                        WHERE username = @username 
                        AND status = 'Active'";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@username", username);

                using var rd = cmd.ExecuteReader();

                if (!rd.Read())
                {
                    return Json(new { success = false, message = "Invalid username or password" });
                }

                var dbPassword = rd["password"]?.ToString();

                // Simple password check (you should hash passwords in production)
                if (dbPassword != password)
                {
                    return Json(new { success = false, message = "Invalid username or password" });
                }

                // Get employee data
                var employeeId = rd["employee_id"].ToString();
                var firstName = rd["first_name"]?.ToString() ?? "";
                var lastName = rd["last_name"]?.ToString() ?? "";
                var level = rd["level"]?.ToString() ?? "";
                var profilePhoto = rd["profile_photo"]?.ToString() ?? "";

                rd.Close();

                // Create claims for session
                var claims = new List<Claim>
                {
                    new Claim("EmployeeId", employeeId),
                    new Claim(ClaimTypes.Name, username),
                    new Claim(ClaimTypes.GivenName, firstName),
                    new Claim(ClaimTypes.Surname, lastName),
                    new Claim(ClaimTypes.Role, "Employee"),
                    new Claim("UserType", "Employee"),
                    new Claim("Level", level),
                    new Claim("ProfilePhoto", profilePhoto),
                    new Claim("FullName", $"{firstName} {lastName}")
                };

                var claimsIdentity = new ClaimsIdentity(claims, CookieAuthenticationDefaults.AuthenticationScheme);
                var claimsPrincipal = new ClaimsPrincipal(claimsIdentity);

                var authProperties = new AuthenticationProperties
                {
                    IsPersistent = rememberMe,
                    ExpiresUtc = rememberMe
                        ? DateTimeOffset.UtcNow.AddDays(30)
                        : DateTimeOffset.UtcNow.AddHours(8)
                };

                HttpContext.SignInAsync(
                    CookieAuthenticationDefaults.AuthenticationScheme,
                    claimsPrincipal,
                    authProperties).Wait();

                return Json(new
                {
                    success = true,
                    message = "Login successful",
                    redirectUrl = "/dashboard/admin"
                });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = "An error occurred: " + ex.Message });
            }
        }

        [HttpGet]
        public async Task<IActionResult> Logout()
        {
            try
            {
                // Clear authentication cookie
                await HttpContext.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme);

                // Clear session if exists
                if (HttpContext.Session != null)
                {
                    HttpContext.Session.Clear();
                }

                // Add cache control headers to prevent back button access
                Response.Headers["Cache-Control"] = "no-cache, no-store, must-revalidate";
                Response.Headers["Pragma"] = "no-cache";
                Response.Headers["Expires"] = "0";

                // Redirect to login page
                return RedirectToAction("Index", "LoginAdmin");
            }
            catch (Exception ex)
            {
                // Even if there's an error, redirect to login
                return RedirectToAction("Index", "LoginAdmin");
            }
        }

        [HttpGet]
        public IActionResult AccessDenied()
        {
            return View("~/Views/Login/AccessDenied.cshtml");
        }
    }
}