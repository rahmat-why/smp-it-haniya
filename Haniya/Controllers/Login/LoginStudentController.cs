using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using System.Security.Claims;

namespace Haniya.Controllers.Login
{
    public class LoginStudentController : Controller
    {
        private readonly IConfiguration _config;

        public LoginStudentController(IConfiguration config)
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
            if (User?.Identity?.IsAuthenticated ?? false)
            {
                var userType = User.FindFirst("UserType")?.Value;
                if (string.Equals(userType, "Student", StringComparison.OrdinalIgnoreCase))
                {
                    return RedirectToAction("Index", "StDashboard");
                }

                if (string.Equals(userType, "Employee", StringComparison.OrdinalIgnoreCase))
                {
                    return RedirectToAction("Admin", "Dashboard");
                }

                if (string.Equals(userType, "Teacher", StringComparison.OrdinalIgnoreCase))
                {
                    return RedirectToAction("Teacher", "Dashboard");
                }

                HttpContext.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme).Wait();
            }

            return View("~/Views/Login/LoginStudent.cshtml");
        }

        [HttpPost]
        public IActionResult Login()
        {
            try
            {
                // GUNAKAN "nis" (bukan nisn)
                var nis = Request.Form["nis"].ToString();
                var password = Request.Form["password"].ToString();
                var rememberMe = Request.Form["remember_me"].ToString() == "true";

                if (string.IsNullOrEmpty(nis) || string.IsNullOrEmpty(password))
                {
                    return Json(new { success = false, message = "NIS dan password wajib diisi" });
                }

                using var conn = GetConn();
                conn.Open();

                var sql = @"
                    SELECT 
                        student_id,
                        first_name,
                        last_name,
                        nis,
                        password,
                        profile_photo,
                        status,
                        full_name
                    FROM mst_students
                    WHERE nis = @nis
                    AND status = 'Active'";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@nis", nis);

                using var rd = cmd.ExecuteReader();

                if (!rd.Read())
                {
                    return Json(new { success = false, message = "NIS atau password tidak valid" });
                }

                var dbPassword = rd["password"]?.ToString();

                // Cek password sederhana (disarankan pakai hash di produksi)
                if (dbPassword != password)
                {
                    return Json(new { success = false, message = "NIS atau password tidak valid" });
                }

                var studentId = rd["student_id"].ToString();
                var firstName = rd["first_name"]?.ToString() ?? "";
                var lastName = rd["last_name"]?.ToString() ?? "";
                var studentNis = rd["nis"]?.ToString() ?? "";
                var profilePhoto = rd["profile_photo"]?.ToString() ?? "";
                var fullNameFromDb = rd["full_name"]?.ToString() ?? "";

                // Jika kolom full_name terisi, pakai itu, kalau tidak gabung first + last
                var fullName = !string.IsNullOrWhiteSpace(fullNameFromDb)
                    ? fullNameFromDb
                    : $"{firstName} {lastName}".Trim();

                rd.Close();

                var claims = new List<Claim>
                {
                    new Claim("StudentId", studentId),
                    // Name = NIS (bisa dipakai di tampilan)
                    new Claim(ClaimTypes.Name, studentNis),
                    new Claim(ClaimTypes.GivenName, firstName),
                    new Claim(ClaimTypes.Surname, lastName),
                    new Claim(ClaimTypes.Role, "Student"),
                    new Claim("UserType", "Student"),

                    new Claim("ProfilePhoto", profilePhoto),
                    new Claim("FullName", fullName),
                    new Claim("NIS", studentNis)
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
                    message = "Login berhasil",
                    redirectUrl = "/StDashboard"
                });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = "Terjadi kesalahan: " + ex.Message });
            }
        }

        [HttpGet]
        public async Task<IActionResult> Logout()
        {
            try
            {
                await HttpContext.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme);

                if (HttpContext.Session != null)
                {
                    HttpContext.Session.Clear();
                }

                Response.Headers["Cache-Control"] = "no-cache, no-store, must-revalidate";
                Response.Headers["Pragma"] = "no-cache";
                Response.Headers["Expires"] = "0";

                return RedirectToAction("Index", "LoginStudent");
            }
            catch
            {
                return RedirectToAction("Index", "LoginStudent");
            }
        }

        [HttpGet]
        public IActionResult AccessDenied()
        {
            return View("~/Views/Login/AccessDenied.cshtml");
        }
    }
}
