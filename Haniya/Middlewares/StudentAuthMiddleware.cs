using System;
using System.Linq;
using System.Security.Claims;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.Logging;

namespace Haniya.Middlewares
{
    public class StudentAuthMiddleware
    {
        private readonly RequestDelegate _next;
        private readonly ILogger<StudentAuthMiddleware> _logger;

        // Daftar path yang hanya boleh diakses oleh siswa yang sudah login
        private static readonly string[] StudentPaths = new[]
        {
            "/StDashboard" // Tambahkan path lain jika ada area siswa lainnya
        };

        public StudentAuthMiddleware(RequestDelegate next, ILogger<StudentAuthMiddleware> logger)
        {
            _next = next;
            _logger = logger;
        }

        public async Task InvokeAsync(HttpContext context)
        {
            var path = context.Request.Path.Value ?? string.Empty;

            // Cek apakah path ini termasuk area siswa
            bool isStudentPath = StudentPaths.Any(p =>
                path.StartsWith(p, StringComparison.OrdinalIgnoreCase));

            if (isStudentPath)
            {
                // 1) Belum login sama sekali → paksa ke halaman login siswa
                if (!(context.User.Identity?.IsAuthenticated ?? false))
                {
                    _logger.LogWarning("Unauthenticated access attempt to student area: {Path}", path);
                    context.Response.Redirect("/LoginStudent/Index");
                    return;
                }

                // 2) Sudah login tapi bukan siswa → tolak akses
                var userType = context.User.FindFirst("UserType")?.Value;
                if (!string.Equals(userType, "Student", StringComparison.OrdinalIgnoreCase))
                {
                    _logger.LogWarning(
                        "Non-student user ({UserType}) attempted to access student area: {Path}",
                        userType ?? "Unknown",
                        path
                    );
                    context.Response.Redirect("/LoginStudent/AccessDenied");
                    return;
                }

                // 3) Siswa aktif, boleh lewat
                var userId = context.User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
                _logger.LogInformation("Student user {UserId} accessing: {Path}", userId, path);
            }

            await _next(context);
        }
    }
}