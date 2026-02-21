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
        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StDashboard/Index.cshtml");
        }

        [HttpGet]
        public IActionResult GetSummary()
        {
            try
            {
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
<<<<<<< HEAD
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
                    FROM mst_schedules s
                    JOIN mst_schedule_details d
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
=======
>>>>>>> d1f0681 (21022026)

                var data = new
                {
                    attendance = GetTotalAttendance(conn, studentId),
                    gradesChart = GetStudentGradesChart(conn, studentId),
                    attendanceChart = GetStudentAttendanceChart(conn, studentId),
                    weeklySchedule = GetStudentWeeklySchedule(conn, studentId),
                    unpaidPayments = GetStudentUnpaidPayments(conn, studentId)
                };

<<<<<<< HEAD
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
                    FROM mst_schedules s
                    JOIN mst_schedule_details d
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
=======
                return Json(new {success = true, data});
>>>>>>> d1f0681 (21022026)
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

        private dynamic GetTotalAttendance(SqlConnection conn, string studentId)
        {
            using var cmd = new SqlCommand(@"
                SELECT
                    COUNT(DISTINCT td.status) AS [Total],
                    c.class_name AS [Kelas]
                FROM txn_attendance_details td
                JOIN mst_students ms ON td.student_id = ms.student_id
                JOIN mst_student_classes msc ON ms.student_id = msc.student_id
                JOIN mst_academic_classes ac ON msc.academic_class_id = ac.academic_class_id
                JOIN mst_classes c ON ac.class_id = c.class_id
                WHERE ms.student_id = @studentId
                GROUP BY c.class_name
                ORDER BY c.class_name;
            ", conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            using var rd = cmd.ExecuteReader();

            int total = 0;
            string kelas = string.Empty;

            if (rd.Read())
            {
                total = rd["Total"] != DBNull.Value ? Convert.ToInt32(rd["Total"]) : 0;
                kelas = rd["Kelas"] != DBNull.Value ? rd["Kelas"].ToString() ?? string.Empty : string.Empty;
            }

            return new
            {
                Total = total,
                Kelas = kelas
            };
        }

        private dynamic GetStudentWeeklySchedule(SqlConnection conn, string studentId)
        {
            var list = new List<dynamic>();

            using var cmd = new SqlCommand(@"
                SELECT
                    s.day,
                    sd.start_time,
                    sd.end_time,
                    sub.subject_name,
                    ISNULL(t.first_name + ' ' + t.last_name, 'Tidak Ditentukan') AS teacher
                FROM txn_schedules s
                JOIN txn_schedule_details sd ON s.schedule_id = sd.schedule_id
                JOIN mst_subjects sub ON sd.subject_id = sub.subject_id
                LEFT JOIN mst_teachers t ON sd.teacher_id = t.teacher_id
                JOIN mst_academic_classes ac ON s.academic_class_id = ac.academic_class_id
                JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
                JOIN mst_student_classes sc ON ac.academic_class_id = sc.academic_class_id
                WHERE sc.student_id = @studentId AND ay.status = 'ACTIVE'
                ORDER BY
                    CASE s.day
                        WHEN 'Senin' THEN 1
                        WHEN 'Selasa' THEN 2
                        WHEN 'Rabu' THEN 3
                        WHEN 'Kamis' THEN 4
                        WHEN 'Jumat' THEN 5
                        WHEN 'Sabtu' THEN 6
                    END, sd.start_time;
            ", conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    day = rd["day"]?.ToString() ?? string.Empty,
                    startTime = rd["start_time"]?.ToString() ?? string.Empty,
                    endTime = rd["end_time"]?.ToString() ?? string.Empty,
                    subject = rd["subject_name"]?.ToString() ?? string.Empty,
                    teacher = rd["teacher"]?.ToString() ?? "Tidak Ditentukan"
                });
            }

            return list;
        }

        private dynamic GetStudentAttendanceChart(SqlConnection conn, string studentId)
        {
            var list = new List<dynamic>();

            using var cmd = new SqlCommand(@"
                SELECT
                    c.class_name AS kelas,
                    ad.status,
                    COUNT(*) AS count
                FROM txn_attendance_details ad
                JOIN txn_attendances a ON ad.attendance_id = a.attendance_id
                JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
                JOIN mst_classes c ON ac.class_id = c.class_id
                WHERE ad.student_id = @studentId
                GROUP BY c.class_name, ad.status
                ORDER BY c.class_name;
            ", conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    kelas = rd["kelas"]?.ToString() ?? string.Empty,
                    status = rd["status"]?.ToString() ?? string.Empty,
                    count = rd["count"] != DBNull.Value ? Convert.ToInt32(rd["count"]) : 0
                });
            }

            return list;
        }

        private dynamic GetStudentGradesChart(SqlConnection conn, string studentId)
        {
            var list = new List<dynamic>();

            using var cmd = new SqlCommand(@"
                SELECT
                    mc.class_name AS Kelas,
                    AVG(CAST(gd.grade_value AS decimal(10,2))) AS AverageGrade
                FROM txn_grades tg
                JOIN txn_grade_details gd ON tg.grade_id = gd.grade_id
                JOIN mst_academic_classes mac ON tg.academic_class_id = mac.academic_class_id
                JOIN mst_classes mc ON mac.class_id = mc.class_id
                WHERE gd.student_id = @studentId
                GROUP BY mc.class_name
                ORDER BY mc.class_name;
            ", conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    kelas = rd["Kelas"]?.ToString() ?? string.Empty,
                    averageGrade = rd["AverageGrade"] != DBNull.Value
                        ? Math.Round(Convert.ToDecimal(rd["AverageGrade"]), 2)
                        : 0m
                });
            }

            return list;
        }

        private dynamic GetStudentUnpaidPayments(SqlConnection conn, string studentId)
        {
            var list = new List<dynamic>();

            using var cmd = new SqlCommand(@"
                SELECT
                    p.remaining_payment AS TotalBelumLunas,
                    p.payment_date AS TanggalPembayaran
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_students s ON sc.student_id = s.student_id
                WHERE s.student_id = @studentId
                AND p.status IN ('PARTIAL', 'UNPAID');
            ", conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    totalBelumLunas = rd["TotalBelumLunas"] != DBNull.Value
                        ? Convert.ToDecimal(rd["TotalBelumLunas"])
                        : 0m,
                    tanggalPembayaran = rd["TanggalPembayaran"] != DBNull.Value
                        ? Convert.ToDateTime(rd["TanggalPembayaran"]).ToString("yyyy-MM-dd")
                        : string.Empty
                });
            }

            return list;
        }
    }
}
