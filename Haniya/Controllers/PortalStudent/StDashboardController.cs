using Microsoft.AspNetCore.Mvc;

namespace Haniya.Controllers.PortalStudent
{
    public class StDashboardController : Controller
    {
        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StDashboard/Index.cshtml");
        }
    }
}
