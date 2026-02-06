using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using System.Security.Claims;

namespace Haniya.Controllers.PortalStudent
{
    [Authorize]
    public class StDashboardController : Controller
    {
        private readonly IConfiguration _config;
        public StDashboardController(IConfiguration config)
        {
            _config = config;
        }
        private SqlConnection GetConn()
        {
            return new SqlConnection(
                _config.GetConnectionString("DefaultConnection")
            );
        }
        /* ===========================
           PAGE
        =========================== */
        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StDashboard/Index.cshtml");
        }
        /* ===========================
           API DASHBOARD
        =========================== */
        [HttpGet]
        public IActionResult GetSummary()
        {
            try
            {
                // Ambil student_id dari login
                var studentId = User.FindFirst("StudentId")?.Value;
                if (string.IsNullOrEmpty(studentId))
                {
                    return Json(new
                    {
                        success = false,
                        message = "Invalid student session"
                    });
                }
                using var conn = GetConn();
                conn.Open();
                /* ===========================
                   1. AVERAGE GRADE
                =========================== */
                var gradeCmd = new SqlCommand(@"
                    SELECT
                        ISNULL(AVG(CAST(d.grade_value AS DECIMAL(10,2))),0)
                    FROM txn_grades g
                    JOIN txn_grade_details d
                        ON g.grade_id = d.grade_id
                    WHERE d.student_id = @sid
                ", conn);
                gradeCmd.Parameters.AddWithValue("@sid", studentId);
                var avgGrade = Convert.ToDecimal(
                    gradeCmd.ExecuteScalar()
                );
                /* ===========================
                   2. ATTENDANCE
                =========================== */
                var attCmd = new SqlCommand(@"
                    SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN d.status='PRESENT' THEN 1 ELSE 0 END) present,
                        SUM(CASE WHEN d.status='ABSENT' THEN 1 ELSE 0 END) absent,
                        SUM(CASE WHEN d.status='LATE' THEN 1 ELSE 0 END) late
                    FROM txn_attendances a
                    JOIN txn_attendance_details d
                        ON a.attendance_id = d.attendance_id
                    WHERE d.student_id = @sid
                ", conn);
                attCmd.Parameters.AddWithValue("@sid", studentId);
                int total = 0, present = 0, absent = 0, late = 0;
                using (var rd = attCmd.ExecuteReader())
                {
                    if (rd.Read())
                    {
                        total = Convert.ToInt32(rd["total"]);
                        present = Convert.ToInt32(rd["present"]);
                        absent = Convert.ToInt32(rd["absent"]);
                        late = Convert.ToInt32(rd["late"]);
                    }
                }
                var attendancePercent = total > 0
                    ? Math.Round((decimal)present / total * 100, 1)
                    : 0;
                /* ===========================
                   3. PAYMENT
                =========================== */
                var payCmd = new SqlCommand(@"
                    SELECT
                        ISNULL(SUM(p.remaining_payment),0)
                    FROM txn_payments p
                    INNER JOIN mst_student_classes sc 
                        ON p.student_class_id = sc.student_class_id
                    WHERE sc.student_id = @sid
                      AND p.status <> 'PAID'
                ", conn);
                payCmd.Parameters.AddWithValue("@sid", studentId);
                var outstanding = Convert.ToDecimal(
                    payCmd.ExecuteScalar()
                );
                /* ===========================
                   4. TODAY SCHEDULE
                =========================== */
                var today = DateTime.Now.DayOfWeek.ToString().ToUpper();
                var schedCountCmd = new SqlCommand(@"
                    SELECT COUNT(*)
                    FROM txn_schedules s
                    JOIN txn_schedule_details d
                        ON s.schedule_id = d.schedule_id
                    JOIN mst_student_classes sc
                        ON sc.academic_class_id = s.academic_class_id
                    WHERE sc.student_id=@sid
                      AND UPPER(s.day)=@day
                ", conn);
                schedCountCmd.Parameters.AddWithValue("@sid", studentId);
                schedCountCmd.Parameters.AddWithValue("@day", today);
                var todaySchedule = Convert.ToInt32(
                    schedCountCmd.ExecuteScalar()
                );
                /* ===========================
                   5. GRADE CHART
                =========================== */
                var gradeChartCmd = new SqlCommand(@"
                    SELECT TOP 6
                        sub.subject_name,
                        d.grade_value AS score
                    FROM txn_grade_details d
                    INNER JOIN txn_grades g 
                        ON g.grade_id = d.grade_id
                    INNER JOIN mst_subjects sub 
                        ON g.subject_id = sub.subject_id
                    WHERE d.student_id = @sid
                    ORDER BY g.grade_date DESC
                ", conn);
                gradeChartCmd.Parameters.AddWithValue("@sid", studentId);
                var gradeLabels = new List<string>();
                var gradeValues = new List<decimal>();
                using (var rd = gradeChartCmd.ExecuteReader())
                {
                    while (rd.Read())
                    {
                        gradeLabels.Add(rd["subject_name"].ToString() ?? "");
                        gradeValues.Add(
                            Convert.ToDecimal(rd["score"])
                        );
                    }
                }

                // Optional: reverse to show oldest → newest on chart (common for line/bar charts)
                // gradeLabels.Reverse();
                // gradeValues.Reverse();

                /* ===========================
                   6. UPCOMING SCHEDULE
                =========================== */
                var schedCmd = new SqlCommand(@"
                    SELECT TOP 5
                        s.day,
                        d.start_time,
                        d.end_time,
                        sub.subject_name,
                        t.full_name AS teacher
                    FROM txn_schedules s
                    JOIN txn_schedule_details d
                        ON s.schedule_id=d.schedule_id
                    JOIN mst_subjects sub
                        ON d.subject_id=sub.subject_id
                    JOIN mst_teachers t
                        ON d.teacher_id=t.teacher_id
                    JOIN mst_student_classes sc
                        ON sc.academic_class_id=s.academic_class_id
                    WHERE sc.student_id=@sid
                    ORDER BY
                        CASE UPPER(s.day)
                            WHEN 'MONDAY' THEN 1
                            WHEN 'TUESDAY' THEN 2
                            WHEN 'WEDNESDAY' THEN 3
                            WHEN 'THURSDAY' THEN 4
                            WHEN 'FRIDAY' THEN 5
                            ELSE 6
                        END,
                        d.start_time
                ", conn);
                schedCmd.Parameters.AddWithValue("@sid", studentId);
                var schedules = new List<object>();
                using (var rd = schedCmd.ExecuteReader())
                {
                    while (rd.Read())
                    {
                        schedules.Add(new
                        {
                            day = rd["day"].ToString(),
                            start = rd["start_time"].ToString(),
                            end = rd["end_time"].ToString(),
                            subject = rd["subject_name"].ToString(),
                            teacher = rd["teacher"].ToString()
                        });
                    }
                }
                /* ===========================
                   RESPONSE
                =========================== */
                return Json(new
                {
                    success = true,
                    data = new
                    {
                        avgGrade = Math.Round(avgGrade, 1),
                        attendance = attendancePercent,
                        payment = outstanding.ToString("N0"),
                        todaySchedule,
                        grades = new
                        {
                            labels = gradeLabels,
                            values = gradeValues
                        },
                        attendanceChart = new
                        {
                            present,
                            absent,
                            late
                        },
                        schedules
                    }
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