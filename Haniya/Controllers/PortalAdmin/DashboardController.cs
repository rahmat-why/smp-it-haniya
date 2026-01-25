using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace Haniya.Controllers.PortalAdmin
{
    [Authorize]
    public class DashboardController : Controller
    {
        public IActionResult Admin()
        {
            return View("~/Views/PortalAdmin/Dashboard/DashboardAdmin.cshtml");
        }

        public IActionResult Teacher()
        {
            return View("~/Views/PortalAdmin/Dashboard/DashboardTeacher.cshtml");
        }
    }
}
