using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using System.Security.Claims;
using Haniya.Models;

namespace Haniya.Controllers.PortalStudent
{
    public class StScheduleController : Controller
    {
        private readonly IConfiguration _config;

        public StScheduleController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }


        /* ===================== PAGE ===================== */

        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StSchedule/Index.cshtml");
        }


        /* ===================== API ===================== */

        [HttpGet]
        public IActionResult GetMySchedule()
        {
            try
            {
                // Ambil student_id dari login
                var studentId = User.FindFirst("StudentId")?.Value;

                if (string.IsNullOrEmpty(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));


                using var conn = GetConn();
                conn.Open();


                var sql = @"
                    SELECT
                        sch.schedule_id,
                        sch.day,
                        sd.schedule_detail_id,
                        sd.start_time,
                        sd.end_time,
                        sub.subject_name,
                        ht.full_name AS teacher_name,
                        c.class_name
                    FROM mst_student_classes sc
                    JOIN mst_academic_classes ac
                        ON sc.academic_class_id = ac.academic_class_id
                    JOIN mst_academic_years ay
                        ON ac.academic_year_id = ay.academic_year_id
                    JOIN mst_schedules sch
                        ON sch.academic_class_id = ac.academic_class_id
                    JOIN mst_schedule_details sd
                        ON sd.schedule_id = sch.schedule_id
                    JOIN mst_subjects sub
                        ON sd.subject_id = sub.subject_id
                    LEFT JOIN mst_teachers ht
                        ON ac.homeroom_teacher_id = ht.teacher_id
                    JOIN mst_classes c
                        ON ac.class_id = c.class_id
                    WHERE sc.student_id = @studentId
                      AND ay.status = 'ACTIVE'
                      AND sch.day IN ('DAY_MON','DAY_TUE','DAY_WED','DAY_THU','DAY_FRI')
                    ORDER BY
                        CASE sch.day
                            WHEN 'DAY_MON' THEN 1
                            WHEN 'DAY_TUE' THEN 2
                            WHEN 'DAY_WED' THEN 3
                            WHEN 'DAY_THU' THEN 4
                            WHEN 'DAY_FRI' THEN 5
                            ELSE 99
                        END,
                        sd.start_time
                    ";

                var list = new List<object>();

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", studentId);

                    using var r = cmd.ExecuteReader();

                    while (r.Read())
                    {
                        list.Add(new
                        {
                            schedule_id = r["schedule_id"]?.ToString(),

                            day = r["day"]?.ToString(),

                            start_time = r["start_time"] == DBNull.Value
                                ? null
                                : ((TimeSpan)r["start_time"]).ToString(@"hh\:mm"),

                            end_time = r["end_time"] == DBNull.Value
                                ? null
                                : ((TimeSpan)r["end_time"]).ToString(@"hh\:mm"),

                            subject = r["subject_name"]?.ToString(),

                            teacher = r["teacher_name"]?.ToString(),

                            class_name = r["class_name"]?.ToString()
                        });
                    }
                }

                return Json(DTOResponse.ok(list));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

    }
}
