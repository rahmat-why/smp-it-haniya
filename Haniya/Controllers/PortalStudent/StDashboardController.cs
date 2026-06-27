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
                var academicYear = GetLatestActiveAcademicYear(conn);

                if (string.IsNullOrEmpty(academicYear))
                {
                    return Json(new
                    {
                        success = false,
                        message = "Academic year tidak ditemukan"
                    });
                }

                var data = new
                {
                    academicYearUsed = academicYear,
                    memorizationAchievement = GetMemorizationAchievement(conn, studentId),
                    attendance = GetTotalAttendance(conn, studentId),
                    gradesChart = GetStudentGradesChart(conn, studentId),
                    attendanceChart = GetStudentAttendanceChart(conn, studentId),
                    weeklySchedule = GetStudentWeeklySchedule(conn, studentId),
                    unpaidPayments = GetStudentUnpaidPayments(conn, studentId),
                    calendarDays = GetAcademicCalendar(conn, academicYear),
                    calendarEvents = GetEventPinPoints(conn, academicYear, studentId),
                    events = GetEvents(conn),
                    articles = GetArticles(conn)
                };

                return Json(new { success = true, data });
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

        [HttpGet]
        public IActionResult GetMemorizationAchievement()
        {
            try
            {
                var studentId = User.FindFirst("StudentId")?.Value;
                if (string.IsNullOrEmpty(studentId))
                {
                    return Json(new { success = false, message = "Invalid student session" });
                }

                using var conn = GetConn();
                conn.Open();
                var value = GetMemorizationAchievement(conn, studentId);
                return Json(new { success = true, data = value });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }

        private string GetMemorizationAchievement(SqlConnection conn, string studentId)
        {
            using var cmd = new SqlCommand(@"
                SELECT TOP 1 ISNULL(ds.item_name, '-') AS memorization_achievement
                FROM mst_students s
                LEFT JOIN mst_detail_settings ds
                    ON s.level = ds.detail_id
                WHERE s.student_id = @studentId
            ", conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            var value = cmd.ExecuteScalar()?.ToString();
            return string.IsNullOrWhiteSpace(value) ? "-" : value;
        }

        private string? GetLatestActiveAcademicYear(SqlConnection conn)
        {
            string? result = null;

            using var cmd = new SqlCommand(@"
                SELECT TOP 1 academic_year_id
                FROM mst_academic_years
                WHERE status = 'ACTIVE'
                ORDER BY academic_year_id DESC;
            ", conn);

            using var rd = cmd.ExecuteReader();
            if (rd.Read())
            {
                result = rd["academic_year_id"]?.ToString();
            }

            return result;
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
                    ISNULL(t.first_name + ' ' + t.last_name, '-') AS teacher
                FROM mst_schedules s
                JOIN mst_schedule_details sd ON s.schedule_id = sd.schedule_id
                JOIN mst_subjects sub ON sd.subject_id = sub.subject_id
                LEFT JOIN mst_teachers t ON sd.teacher_id = t.teacher_id
                JOIN mst_academic_classes ac ON s.academic_class_id = ac.academic_class_id
                JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
                JOIN mst_student_classes sc ON ac.academic_class_id = sc.academic_class_id
                WHERE sc.student_id = @studentId AND ay.status = 'ACTIVE'
                ORDER BY
                    CASE s.day
                        WHEN 'DAY_MON' THEN 1
                        WHEN 'DAY_TUE' THEN 2
                        WHEN 'DAY_WED' THEN 3
                        WHEN 'DAY_THU' THEN 4
                        WHEN 'DAY_FRI' THEN 5
                        WHEN 'DAY_SAT' THEN 6
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
                    teacher = rd["teacher"]?.ToString() ?? "-"
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
                    p.payment_id,
                    p.payment_type,
                    COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc,
                    p.remaining_payment AS total_belum_lunas,
                    p.due_date AS due_date,
                    p.status
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_students s ON sc.student_id = s.student_id
                LEFT JOIN mst_detail_settings pt
                    ON p.payment_type = pt.detail_id
                   AND pt.header_id = 'PAYMENT_TYPE'
                WHERE s.student_id = @studentId
                AND p.status IN ('PARTIAL', 'UNPAID')
                ORDER BY p.due_date ASC;
            ", conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    paymentId = rd["payment_id"]?.ToString() ?? string.Empty,
                    paymentType = rd["payment_type"]?.ToString() ?? string.Empty,
                    paymentTypeDesc = rd["payment_type_desc"]?.ToString() ?? string.Empty,
                    status = rd["status"]?.ToString() ?? string.Empty,
                    remainingPayment = rd["total_belum_lunas"] != DBNull.Value
                        ? Convert.ToDecimal(rd["total_belum_lunas"])
                        : 0m,
                    dueDate = rd["due_date"] != DBNull.Value
                        ? Convert.ToDateTime(rd["due_date"]).ToString("yyyy-MM-dd")
                        : string.Empty
                });
            }

            return list;
        }

        private List<dynamic> GetAcademicCalendar(SqlConnection conn, string academicYear)
        {
            var list = new List<dynamic>();
            var sql = @"
                SELECT
                    REPLACE(academic_year_id, 'ACY/', '') AS academic_year,
                    [date] AS calendar_date,
                    [day] AS calendar_day,
                    is_weekend
                FROM mst_calendars
                WHERE academic_year_id = @academicYear
                ORDER BY [date]";

            using var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@academicYear", academicYear);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    academic_year = rd["academic_year"]?.ToString(),
                    calendar_date = rd["calendar_date"] != DBNull.Value ? Convert.ToDateTime(rd["calendar_date"]) : (DateTime?)null,
                    calendar_day = rd["calendar_day"]?.ToString(),
                    is_weekend = rd["is_weekend"] != DBNull.Value && Convert.ToInt32(rd["is_weekend"]) == 1
                });
            }

            return list;
        }

        private List<dynamic> GetEventPinPoints(SqlConnection conn, string academicYear, string studentId)
        {
            var list = new List<dynamic>();
            var sql = @"
                SELECT
                    me.event_id AS event_id,
                    me.event_name AS event_name,
                    mec.class_level AS class_level,
                    me.start_date AS start_date,
                    me.end_date AS end_date,
                    mec.is_holiday AS is_holiday
                FROM mst_event_classes mec
                JOIN mst_events me ON mec.event_id = me.event_id
                WHERE mec.class_level = (
                    SELECT TOP 1 SUBSTRING(mac.academic_class_id, 4, 1)
                    FROM mst_student_classes msc
                    JOIN mst_academic_classes mac ON msc.academic_class_id = mac.academic_class_id
                    WHERE msc.student_id = @studentId
                    ORDER BY mac.academic_class_id DESC
                )
                AND EXISTS (
                    SELECT 1
                    FROM mst_calendars c
                    WHERE c.academic_year_id = @academicYear
                    AND c.[date] BETWEEN me.start_date AND me.end_date
                )
                ORDER BY me.start_date, me.event_name";

            using var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@academicYear", academicYear);
            cmd.Parameters.AddWithValue("@studentId", studentId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    event_id = rd["event_id"]?.ToString(),
                    event_name = rd["event_name"]?.ToString(),
                    class_level = rd["class_level"]?.ToString(),
                    start_date = rd["start_date"] != DBNull.Value ? Convert.ToDateTime(rd["start_date"]) : (DateTime?)null,
                    end_date = rd["end_date"] != DBNull.Value ? Convert.ToDateTime(rd["end_date"]) : (DateTime?)null,
                    is_holiday = rd["is_holiday"] != DBNull.Value && Convert.ToInt32(rd["is_holiday"]) == 1
                });
            }

            return list;
        }

        private List<dynamic> GetEvents(SqlConnection conn)
        {
            var list = new List<dynamic>();
            var cmd = new SqlCommand(@"
                SELECT TOP 6 e.event_id, e.event_name, e.description, e.location, e.created_at, e.profile_photo
                FROM mst_events e
                WHERE e.status = 'ACTIVE'
                ORDER BY e.created_at DESC", conn);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                var eventId = rd["event_id"].ToString();
                list.Add(new
                {
                    id = eventId,
                    name = rd["event_name"],
                    desc = rd["description"],
                    location = rd["location"],
                    date = ((DateTime)rd["created_at"]).ToString("dd MMM yyyy"),
                    photo = rd["profile_photo"] ?? "/image/default-event.jpg",
                    tags = GetEventTags(conn, eventId)
                });
            }
            return list;
        }

        private List<string> GetEventTags(SqlConnection conn, string eventId)
        {
            var tags = new List<string>();
            var tagCmd = new SqlCommand(@"
                SELECT d.item_desc
                FROM mst_tag_events t
                JOIN mst_detail_settings d ON t.tag_code = d.item_code AND d.header_id = 'TAG_EVENT'
                WHERE t.event_id = @eventId", conn);
            tagCmd.Parameters.AddWithValue("@eventId", eventId);
            using var tagRd = tagCmd.ExecuteReader();
            while (tagRd.Read())
            {
                tags.Add(tagRd["item_desc"].ToString());
            }
            return tags;
        }

        private List<dynamic> GetArticles(SqlConnection conn)
        {
            var list = new List<dynamic>();
            var cmd = new SqlCommand(@"
                SELECT TOP 6 a.article_id, a.title, a.content, a.image, a.created_at
                FROM mst_articles a
                WHERE a.status = 'PUBLISHED'
                ORDER BY a.created_at DESC", conn);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                var articleId = rd["article_id"].ToString();
                list.Add(new
                {
                    id = articleId,
                    title = rd["title"],
                    content = rd["content"].ToString().Length > 150 ? rd["content"].ToString().Substring(0, 150) + "..." : rd["content"],
                    image = rd["image"] ?? "/image/default-article.jpg",
                    date = ((DateTime)rd["created_at"]).ToString("dd MMM yyyy"),
                    tags = GetArticleTags(conn, articleId)
                });
            }
            return list;
        }

        private List<string> GetArticleTags(SqlConnection conn, string articleId)
        {
            var tags = new List<string>();
            var tagCmd = new SqlCommand(@"
                SELECT d.item_desc
                FROM mst_tag_articles t
                JOIN mst_detail_settings d ON t.tag_code = d.item_code AND d.header_id = 'TAG_ARTICLE'
                WHERE t.article_id = @articleId", conn);
            tagCmd.Parameters.AddWithValue("@articleId", articleId);
            using var tagRd = tagCmd.ExecuteReader();
            while (tagRd.Read())
            {
                tags.Add(tagRd["item_desc"].ToString());
            }
            return tags;
        }

        [HttpGet]
        public IActionResult GetReminders(int page = 1, int limit = 10)
        {
            try
            {
                var studentId = User.FindFirst("StudentId")?.Value;
                if (string.IsNullOrEmpty(studentId)) return Json(new { success = false, message = "Unauthorized" });

                var offset = (page - 1) * limit;

                using var conn = GetConn();
                conn.Open();

                // Diagnostic Logging to a file
                try
                {
                    var debugPath = Path.Combine(Directory.GetCurrentDirectory(), "reminder_debug.txt");
                    var debugInfo = $"Time: {DateTime.Now}\nStudentId: {studentId ?? "NULL"}\n";
                    
                    using (var cmdRaw = new SqlCommand("SELECT COUNT(*) FROM txn_assignment_reminders WHERE student_id = @sid", conn))
                    {
                        cmdRaw.Parameters.AddWithValue("@sid", studentId ?? (object)DBNull.Value);
                        debugInfo += $"Raw reminders count for student: {cmdRaw.ExecuteScalar()}\n";
                    }
                    using (var cmdRaw = new SqlCommand("SELECT nis, password FROM mst_students WHERE student_id = @sid", conn))
                    {
                        cmdRaw.Parameters.AddWithValue("@sid", studentId ?? (object)DBNull.Value);
                        using var rdCreds = cmdRaw.ExecuteReader();
                        if (rdCreds.Read())
                        {
                            debugInfo += $"Student NIS: {rdCreds["nis"]}, Password: {rdCreds["password"]}\n";
                        }
                    }
                    using (var cmdRaw = new SqlCommand("SELECT COUNT(*) FROM txn_assignments", conn))
                    {
                        debugInfo += $"Total assignments in DB: {cmdRaw.ExecuteScalar()}\n";
                    }
                    using (var cmdRaw = new SqlCommand(@"
                        SELECT TOP 5 r.reminder_id, r.assignment_id, r.student_id, a.assignment_id AS a_aid, a.subject_id
                        FROM txn_assignment_reminders r
                        LEFT JOIN txn_assignments a ON r.assignment_id = a.assignment_id
                        WHERE r.student_id = @sid", conn))
                    {
                        cmdRaw.Parameters.AddWithValue("@sid", studentId ?? (object)DBNull.Value);
                        using var rdRaw = cmdRaw.ExecuteReader();
                        while (rdRaw.Read())
                        {
                            debugInfo += $"Reminder ID: {rdRaw["reminder_id"]}, Assignment ID: {rdRaw["assignment_id"]}, Student ID: {rdRaw["student_id"]}, A_AID: {rdRaw["a_aid"] ?? "NULL"}, Subject ID: {rdRaw["subject_id"] ?? "NULL"}\n";
                        }
                    }
                    System.IO.File.WriteAllText(debugPath, debugInfo);
                }
                catch (Exception debugEx)
                {
                    var debugPath = Path.Combine(Directory.GetCurrentDirectory(), "reminder_debug.txt");
                    System.IO.File.WriteAllText(debugPath, "Debug Error: " + debugEx.ToString());
                }

                using var countCmd = new SqlCommand(@"
                    SELECT COUNT(*)
                    FROM txn_assignment_reminders r
                    JOIN txn_assignments a ON r.assignment_id = a.assignment_id
                    JOIN mst_subjects s ON a.subject_id = s.subject_id
                    WHERE r.student_id = @studentId", conn);
                countCmd.Parameters.AddWithValue("@studentId", studentId);
                var total = (int)countCmd.ExecuteScalar();

                using var unreadCountCmd = new SqlCommand("SELECT COUNT(*) FROM txn_assignment_reminders WHERE student_id = @studentId AND is_read = 0", conn);
                unreadCountCmd.Parameters.AddWithValue("@studentId", studentId);
                var unreadCount = (int)unreadCountCmd.ExecuteScalar();

                var sql = @"
                    SELECT r.reminder_id, a.title, a.description, s.subject_name, a.due_date, r.is_read, a.created_at,
                           t.first_name, t.last_name
                    FROM txn_assignment_reminders r
                    JOIN txn_assignments a ON r.assignment_id = a.assignment_id
                    JOIN mst_subjects s ON a.subject_id = s.subject_id
                    LEFT JOIN mst_teachers t ON a.teacher_id = t.teacher_id
                    WHERE r.student_id = @studentId
                    ORDER BY r.is_read ASC, a.created_at DESC, r.reminder_id ASC
                    OFFSET @offset ROWS FETCH NEXT @limit ROWS ONLY";

                var list = new List<object>();
                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", studentId);
                    cmd.Parameters.AddWithValue("@offset", offset);
                    cmd.Parameters.AddWithValue("@limit", limit);
                    using var rd = cmd.ExecuteReader();
                    while (rd.Read())
                    {
                        list.Add(new
                        {
                            reminder_id = rd["reminder_id"].ToString(),
                            title = rd["title"].ToString(),
                            description = rd["description"]?.ToString(),
                            subject_name = rd["subject_name"].ToString(),
                            teacher_name = (rd["first_name"]?.ToString() + " " + rd["last_name"]?.ToString()).Trim(),
                            due_date = rd["due_date"] != DBNull.Value ? Convert.ToDateTime(rd["due_date"]).ToString("dd MMM yyyy") : "No Due Date",
                            is_read = rd["is_read"] != DBNull.Value ? Convert.ToBoolean(rd["is_read"]) : false
                        });
                    }
                }

                var more = (page * limit) < total;
                return Json(new { success = true, data = list, more, total, unreadCount });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }
        [HttpPost]
        public IActionResult MarkReminderRead(string id)
        {
            try
            {
                var studentId = User.FindFirst("StudentId")?.Value;
                if (string.IsNullOrEmpty(studentId)) return Json(new { success = false, message = "Unauthorized" });

                using var conn = GetConn();
                conn.Open();

                using var cmd = new SqlCommand("UPDATE txn_assignment_reminders SET is_read = 1 WHERE reminder_id = @id AND student_id = @studentId", conn);
                cmd.Parameters.AddWithValue("@id", id);
                cmd.Parameters.AddWithValue("@studentId", studentId);
                var rows = cmd.ExecuteNonQuery();

                return Json(new { success = rows > 0 });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }
    }
}
