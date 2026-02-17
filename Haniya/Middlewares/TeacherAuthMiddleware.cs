using System;
using System.Linq;
using System.Security.Claims;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.Logging;

namespace Haniya.Middlewares
{
    public class TeacherAuthMiddleware
    {
        private readonly RequestDelegate _next;
        private readonly ILogger<TeacherAuthMiddleware> _logger;

        // Daftar path yang hanya boleh diakses oleh guru yang sudah login
        private static readonly string[] TeacherPaths = new[]
        {
            "/Dashboard/Teacher"
        };

        public TeacherAuthMiddleware(RequestDelegate next, ILogger<TeacherAuthMiddleware> logger)
        {
            _next = next;
            _logger = logger;
        }

        public async Task InvokeAsync(HttpContext context)
        {
            var path = context.Request.Path.Value ?? string.Empty;

            // Cek apakah path ini termasuk area guru
            bool isTeacherPath = TeacherPaths.Any(p =>
                path.StartsWith(p, StringComparison.OrdinalIgnoreCase));

            if (isTeacherPath)
            {
                // 1) Belum login sama sekali → paksa ke halaman login guru
                if (!(context.User.Identity?.IsAuthenticated ?? false))
                {
                    _logger.LogWarning("Unauthenticated access attempt to teacher area: {Path}", path);
                    context.Response.Redirect("/LoginTeacher/Index");
                    return;
                }

                // 2) Sudah login tapi bukan guru → tolak akses
                var userType = context.User.FindFirst("UserType")?.Value;
                if (!string.Equals(userType, "Teacher", StringComparison.OrdinalIgnoreCase))
                {
                    _logger.LogWarning(
                        "Non-teacher user ({UserType}) attempted to access teacher area: {Path}",
                        userType,
                        path
                    );
                    context.Response.Redirect("/LoginAdmin/AccessDenied");
                    return;
                }

                // 3) Guru aktif, boleh lewat
                var userId = context.User.FindFirst("TeacherId")?.Value;
                _logger.LogInformation("Teacher user {UserId} accessing: {Path}", userId, path);
            }

            await _next(context);
        }
    }
}