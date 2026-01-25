namespace Haniya.Middlewares
{
    public class AdminAuthMiddleware
    {
        private readonly RequestDelegate _next;
        private readonly ILogger<AdminAuthMiddleware> _logger;

        // Define admin-only paths
        private static readonly string[] AdminPaths = new[]
        {
            "/Dashboard/Admin"
        };

        public AdminAuthMiddleware(RequestDelegate next, ILogger<AdminAuthMiddleware> logger)
        {
            _next = next;
            _logger = logger;
        }

        public async Task InvokeAsync(HttpContext context)
        {
            var path = context.Request.Path.Value ?? "";

            // Check if this is an admin path
            bool isAdminPath = AdminPaths.Any(p =>
                path.StartsWith(p, StringComparison.OrdinalIgnoreCase));

            if (isAdminPath)
            {
                // Check if user is authenticated
                if (!context.User.Identity?.IsAuthenticated ?? true)
                {
                    _logger.LogWarning($"Unauthenticated access attempt to admin area: {path}");
                    context.Response.Redirect("/LoginAdmin/Index");
                    return;
                }

                // Check if user is an Employee (Admin)
                var userType = context.User.FindFirst("UserType")?.Value;
                if (userType != "Employee")
                {
                    _logger.LogWarning($"Non-admin user ({userType}) attempted to access admin area: {path}");
                    context.Response.Redirect("/LoginAdmin/AccessDenied");
                    return;
                }

                // Optional: Check if employee status is still active
                var userId = context.User.FindFirst(System.Security.Claims.ClaimTypes.NameIdentifier)?.Value;
                _logger.LogInformation($"Admin user {userId} accessing: {path}");
            }

            await _next(context);
        }
    }
}