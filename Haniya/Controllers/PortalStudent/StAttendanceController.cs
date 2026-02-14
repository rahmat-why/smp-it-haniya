using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using System.Security.Claims;
using Haniya.Models;

namespace Haniya.Controllers.PortalStudent
{
    public class StAttendanceController : Controller
    {
        private readonly IConfiguration _config;

        public StAttendanceController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        // View
        public IActionResult Index()
        {
            // return View("~/Views/PortalStudent/StAttendance/Index.cshtml");
            return View("~/Views/PortalStudent/StPayment/Index.cshtml");
        }

        [HttpGet]
        public IActionResult GetMyAttendance()
        {
            try
            {
                // Ambil student_id dari Claims Login
                var studentId = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;

                if (string.IsNullOrEmpty(studentId))
                {
                    return Json(new
                    {
                        success = false,
                        message = "User not logged in"
                    });
                }

                using var conn = GetConn();
                conn.Open();

                var sql = @"
                    SELECT
                        a.attendance_id,
                        a.attendance_date,

                        d.status,
                        d.notes,

                        c.class_name,
                        t.full_name AS teacher_name

                    FROM txn_attendances a

                    JOIN txn_attendance_details d
                        ON a.attendance_id = d.attendance_id

                    JOIN mst_academic_classes c
                        ON a.academic_class_id = c.academic_class_id

                    JOIN mst_teachers t
                        ON a.teacher_id = t.teacher_id

                    WHERE d.student_id = @student_id

                    ORDER BY a.attendance_date DESC
                ";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@student_id", studentId);

                using var rd = cmd.ExecuteReader();

                var list = new List<object>();

                while (rd.Read())
                {
                    list.Add(new
                    {
                        date = Convert.ToDateTime(rd["attendance_date"])
                                    .ToString("yyyy-MM-dd"),

                        status = rd["status"]?.ToString(),
                        notes = rd["notes"]?.ToString(),
                        class_name = rd["class_name"]?.ToString(),
                        teacher = rd["teacher_name"]?.ToString()
                    });
                }

                return Json(new
                {
                    success = true,
                    data = list
                });
            }
            catch (Exception ex)
            {
                return Json(new
                {
                    success = false,
                    message = ex.Message
                });
            }
        }
    }
}
