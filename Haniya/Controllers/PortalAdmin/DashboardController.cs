using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Newtonsoft.Json;

namespace Haniya.Controllers.PortalAdmin
{
    [Authorize]
    public class DashboardController : Controller
    {
        private readonly IConfiguration _config;

        public DashboardController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        public IActionResult Admin()
        {
            return View("~/Views/PortalAdmin/Dashboard/DashboardAdmin.cshtml");
        }

        public IActionResult Teacher()
        {
            return View("~/Views/PortalAdmin/Dashboard/DashboardTeacher.cshtml");
        }

        [HttpGet]
        public IActionResult GetAdminData(string startDate = null, string endDate = null, string className = null)
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                var data = new
                {
                    totalClass = (int)new SqlCommand("SELECT COUNT(*) FROM mst_classes", conn).ExecuteScalar(),
                    totalStudent = (int)new SqlCommand("SELECT COUNT(*) FROM mst_students WHERE status = 'ACTIVE'", conn).ExecuteScalar(),
                    totalSubject = (int)new SqlCommand("SELECT COUNT(*) FROM mst_subjects WHERE status = 'ACTIVE'", conn).ExecuteScalar(),
                    totalTeacher = (int)new SqlCommand("SELECT COUNT(*) FROM mst_teachers WHERE status = 'ACTIVE'", conn).ExecuteScalar(),
                    payment = GetPaymentSummary(conn, startDate, endDate, className),
                    events = GetEvents(conn),
                    articles = GetArticles(conn)
                };

                return Json(new { success = true, data });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }

        private object GetPaymentSummary(SqlConnection conn, string startDate, string endDate, string className)
        {
            var sql = @"
                SELECT 
                    ISNULL(SUM(total_price), 0) AS total_tagihan,
                    ISNULL(SUM(total_payment), 0) AS total_terbayar,
                    ISNULL(SUM(remaining_payment), 0) AS total_sisa,
                    COUNT(*) AS total_transaction
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                JOIN mst_classes c ON ac.class_id = c.class_id
                WHERE 1=1";

            if (!string.IsNullOrEmpty(startDate) && !string.IsNullOrEmpty(endDate))
            {
                sql += " AND p.payment_date BETWEEN @startDate AND @endDate";
            }
            else
            {
                sql += " AND MONTH(p.payment_date) = MONTH(GETDATE()) AND YEAR(p.payment_date) = YEAR(GETDATE())";
            }

            if (!string.IsNullOrEmpty(className))
            {
                sql += " AND c.class_name = @className";
            }

            var cmd = new SqlCommand(sql, conn);
            if (!string.IsNullOrEmpty(startDate) && !string.IsNullOrEmpty(endDate))
            {
                cmd.Parameters.AddWithValue("@startDate", startDate);
                cmd.Parameters.AddWithValue("@endDate", endDate + " 23:59:59");
            }
            if (!string.IsNullOrEmpty(className))
            {
                cmd.Parameters.AddWithValue("@className", className);
            }

            using var rd = cmd.ExecuteReader();
            if (rd.Read())
            {
                return new
                {
                    tagihan = (decimal)(rd["total_tagihan"] ?? 0),
                    terbayar = (decimal)(rd["total_terbayar"] ?? 0),
                    sisa = (decimal)(rd["total_sisa"] ?? 0),
                    transaction = (int)(rd["total_transaction"] ?? 0)
                };
            }
            return new { tagihan = 0m, terbayar = 0m, sisa = 0m, transaction = 0 };
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
        public IActionResult GetTeacherData(string classId = null, string subjectId = null, string attendanceStart = null, string attendanceEnd = null)
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                var data = new
                {
                    weeklySchedule = GetWeeklySchedule(conn, classId),
                    attendancePie = GetAttendancePie(conn, classId, attendanceStart, attendanceEnd),
                    grades = GetGrades(conn, classId, subjectId),
                    events = GetEvents(conn),
                    articles = GetArticles(conn)
                };

                return Json(new { success = true, data });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }

        private List<dynamic> GetWeeklySchedule(SqlConnection conn, string classId)
        {
            var list = new List<dynamic>();

            // Jika tidak ada kelas yang dipilih, kembalikan list kosong (default empty)
            if (string.IsNullOrEmpty(classId))
            {
                return list;
            }

            var sql = @"
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
        WHERE ac.academic_class_id = @classId
        ORDER BY
            CASE s.day
                WHEN 'Senin' THEN 1
                WHEN 'Selasa' THEN 2
                WHEN 'Rabu' THEN 3
                WHEN 'Kamis' THEN 4
                WHEN 'Jumat' THEN 5
                WHEN 'Sabtu' THEN 6
            END, sd.start_time";

            var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@classId", classId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    day = rd["day"].ToString(),
                    time = rd["start_time"].ToString() + " - " + rd["end_time"].ToString(),
                    subject = rd["subject_name"].ToString(),
                    teacher = rd["teacher"].ToString()
                });
            }

            return list;
        }

        private List<dynamic> GetAttendancePie(SqlConnection conn, string classId, string startDate, string endDate)
        {
            var list = new List<dynamic>();
            var sql = @"
                SELECT ad.status, COUNT(*) AS count
                FROM txn_attendance_details ad
                JOIN txn_attendances a ON ad.attendance_id = a.attendance_id
                JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
                WHERE 1=1";

            if (!string.IsNullOrEmpty(startDate) && !string.IsNullOrEmpty(endDate))
            {
                sql += " AND a.attendance_date BETWEEN @startDate AND @endDate";
            }
            else
            {
                sql += " AND MONTH(a.attendance_date) = MONTH(GETDATE()) AND YEAR(a.attendance_date) = YEAR(GETDATE())";
            }

            if (!string.IsNullOrEmpty(classId))
                sql += " AND ac.academic_class_id = @classId";

            sql += " GROUP BY ad.status";

            var cmd = new SqlCommand(sql, conn);
            if (!string.IsNullOrEmpty(startDate) && !string.IsNullOrEmpty(endDate))
            {
                cmd.Parameters.AddWithValue("@startDate", startDate);
                cmd.Parameters.AddWithValue("@endDate", endDate + " 23:59:59");
            }
            if (!string.IsNullOrEmpty(classId))
                cmd.Parameters.AddWithValue("@classId", classId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    status = rd["status"],
                    count = (int)rd["count"]
                });
            }
            return list;
        }

        private List<dynamic> GetGrades(SqlConnection conn, string classId, string subjectId)
        {
            var list = new List<dynamic>();

            // Jika kelas belum dipilih, kembalikan empty (hindari query semua data)
            if (string.IsNullOrEmpty(classId))
            {
                return list;
            }

            var sql = @"
        SELECT
            s.full_name AS student,
            sub.subject_name AS subject,
            AVG(gd.grade_value) AS avg_grade
        FROM txn_grade_details gd
        JOIN txn_grades g ON gd.grade_id = g.grade_id
        JOIN mst_subjects sub ON g.subject_id = sub.subject_id
        JOIN mst_students s ON gd.student_id = s.student_id
        JOIN mst_academic_classes ac ON g.academic_class_id = ac.academic_class_id
        WHERE ac.academic_class_id = @classId";

            if (!string.IsNullOrEmpty(subjectId))
                sql += " AND sub.subject_id = @subjectId";

            sql += " GROUP BY s.full_name, sub.subject_name ORDER BY s.full_name";

    var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@classId", classId);
            if (!string.IsNullOrEmpty(subjectId))
                cmd.Parameters.AddWithValue("@subjectId", subjectId);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    student = rd["student"].ToString(),
                    subject = rd["subject"].ToString(),
                    avg = Math.Round(Convert.ToDecimal(rd["avg_grade"]), 2)
                });
            }

            return list;
        }
    }
}