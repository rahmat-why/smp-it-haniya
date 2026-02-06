using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using System.Security.Claims;

namespace Haniya.Controllers.Login
{
    public class LoginTeacherController : Controller
    {
        private readonly IConfiguration _config;

        public LoginTeacherController(IConfiguration config)
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
            // If already logged in as teacher, redirect to dashboard
            if (User.Identity.IsAuthenticated && User.HasClaim("UserType", "Teacher"))
            {
                return RedirectToAction("Teacher", "Dashboard");
            }

            return View("~/Views/Login/LoginTeacher.cshtml");
        }

        [HttpPost]
        public IActionResult Login()
        {
            try
            {
                var npk = Request.Form["npk"].ToString();
                var password = Request.Form["password"].ToString();
                var rememberMe = Request.Form["remember_me"].ToString() == "true";

                if (string.IsNullOrEmpty(npk) || string.IsNullOrEmpty(password))
                {
                    return Json(new { success = false, message = "NPK and password are required" });
                }

                using var conn = GetConn();
                conn.Open();

                var sql = @"SELECT 
                            teacher_id, 
                            first_name, 
                            last_name, 
                            npk, 
                            password,
                            level, 
                            status,
                            profile_photo
                        FROM mst_teachers 
                        WHERE npk = @npk 
                        AND status = 'Active'";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@npk", npk);

                using var rd = cmd.ExecuteReader();

                if (!rd.Read())
                {
                    return Json(new { success = false, message = "Invalid NPK or password" });
                }

                var dbPassword = rd["password"]?.ToString();

                // Simple password check (you should hash passwords in production)
                if (dbPassword != password)
                {
                    return Json(new { success = false, message = "Invalid NPK or password" });
                }

                // Get teacher data
                var teacherId = rd["teacher_id"].ToString();
                var firstName = rd["first_name"]?.ToString() ?? "";
                var lastName = rd["last_name"]?.ToString() ?? "";
                var teacherNpk = rd["npk"]?.ToString() ?? "";
                var level = rd["level"]?.ToString() ?? "";
                var profilePhoto = rd["profile_photo"]?.ToString() ?? "";

                rd.Close();

                // Create claims for session
                var claims = new List<Claim>
                {
                    new Claim("TeacherId", teacherId),
                    new Claim(ClaimTypes.Name, teacherNpk),
                    new Claim(ClaimTypes.GivenName, firstName),
                    new Claim(ClaimTypes.Surname, lastName),
                    new Claim(ClaimTypes.Role, "Teacher"),
                    new Claim("UserType", "Teacher"),
                    new Claim("Level", level),
                    new Claim("ProfilePhoto", profilePhoto),
                    new Claim("FullName", $"{firstName} {lastName}"),
                    new Claim("NPK", teacherNpk)
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
                    redirectUrl = "/Dashboard/Teacher"
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
                return RedirectToAction("Index", "LoginTeacher");
            }
            catch (Exception ex)
            {
                // Even if there's an error, redirect to login
                return RedirectToAction("Index", "LoginTeacher");
            }
        }

        [HttpGet]
        public IActionResult AccessDenied()
        {
            return View("~/Views/Login/AccessDenied.cshtml");
        }
    }
}