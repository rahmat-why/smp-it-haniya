using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Newtonsoft.Json;
using System.Security.Claims;
using System.Runtime.Intrinsics.Arm;

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
        public IActionResult GetAdminData(string? academicYear)
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                // Kalo null / kosong → ambil latest ACTIVE
                if (string.IsNullOrEmpty(academicYear))
                {
                    academicYear = GetLatestActiveAcademicYear(conn);
                }

                if (string.IsNullOrEmpty(academicYear))
                {
                    return Json(new { success = false, message = "Academic year tidak ditemukan" });
                }

                var data = new
                {
                    academicYearUsed = academicYear,
                    payment = GetPaymentSummary(conn, academicYear),
                    studentPayment = GetDashboardSummary(conn, academicYear),
                    academicYearList = GetAcademicYearsList(conn),
                    teachers = GetActiveTeacherSummary(conn),
                    students = GetStudentCountPerClass(conn, academicYear),
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

            return result; // null kalo ga ada yang ACTIVE
        }


        private List<dynamic> GetStudentCountPerClass(SqlConnection conn, string academicYear)
        {
            var list = new List<dynamic>();

            using var cmd = new SqlCommand(@"
                SELECT 
                    SUBSTRING(sc.academic_class_id, 4, 1) AS ClassNumber, 
                    COUNT(*) AS TotalStudents
                FROM mst_student_classes sc
                JOIN mst_academic_classes ac 
                    ON sc.academic_class_id = ac.academic_class_id
                WHERE SUBSTRING(sc.academic_class_id, 4, 1) IN ('7','8','9') 
                AND ac.academic_year_id = @academicYear
                GROUP BY SUBSTRING(sc.academic_class_id, 4, 1)
                ORDER BY ClassNumber;
            ", conn);

            cmd.Parameters.AddWithValue("@academicYear", academicYear);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    ClassNumber = rd["ClassNumber"],
                    TotalStudents = rd["TotalStudents"]
                });
            }

            return list;
        }


        private dynamic GetActiveTeacherSummary(SqlConnection conn)
        {
            using var cmd = new SqlCommand(@"
                SELECT
                    COUNT(*) AS Total,
                    SUM(CASE WHEN level = 'PNS' THEN 1 ELSE 0 END) AS PNS,
                    SUM(CASE WHEN level = 'HONORER' THEN 1 ELSE 0 END) AS Honorer
                FROM mst_teachers
                WHERE status = 'ACTIVE';
            ", conn);

            using var rd = cmd.ExecuteReader();

            int total = 0, pns = 0, honorer = 0;

            if (rd.Read())
            {
                total = rd["Total"] != DBNull.Value ? Convert.ToInt32(rd["Total"]) : 0;
                pns = rd["PNS"] != DBNull.Value ? Convert.ToInt32(rd["PNS"]) : 0;
                honorer = rd["Honorer"] != DBNull.Value ? Convert.ToInt32(rd["Honorer"]) : 0;
            }

            return new
            {
                Total = total,
                PNS = pns,
                Honorer = honorer
            };
        }

        private object GetPaymentSummary(SqlConnection conn, string academicYearId)
        {
            // Total Lunas (yang status PAID)
            decimal totalLunas = 0;
            using (var cmd = new SqlCommand(@"
                SELECT ISNULL(SUM(p.total_payment + p.remaining_payment), 0) AS TotalLunas
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                WHERE ac.academic_year_id = @AcademicYearId 
                AND p.status = 'PAID'
            ", conn))
            {
                cmd.Parameters.AddWithValue("@AcademicYearId", academicYearId ?? (object)DBNull.Value);
                var result = cmd.ExecuteScalar();
                totalLunas = result != DBNull.Value ? Convert.ToDecimal(result) : 0m;
            }

            // Total Belum Lunas (yang status PARTIAL atau UNPAID)
            decimal totalBelumLunas = 0;
            using (var cmd = new SqlCommand(@"
                SELECT ISNULL(SUM(p.remaining_payment), 0) AS TotalBelumLunas
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                WHERE ac.academic_year_id = @AcademicYearId
                AND p.status IN ('PARTIAL', 'UNPAID')
            ", conn))
            {
                cmd.Parameters.AddWithValue("@AcademicYearId", academicYearId ?? (object)DBNull.Value);
                var result = cmd.ExecuteScalar();
                totalBelumLunas = result != DBNull.Value ? Convert.ToDecimal(result) : 0m;
            }

            // Total Pembayaran Masuk (yang sudah dibayar)
            decimal totalPembayaranMasuk = 0;
            using (var cmd = new SqlCommand(@"
                SELECT ISNULL(SUM(p.total_payment), 0) AS TotalPembayaranMasuk
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                WHERE ac.academic_year_id = @AcademicYearId
            ", conn))
            {
                cmd.Parameters.AddWithValue("@AcademicYearId", academicYearId ?? (object)DBNull.Value);
                var result = cmd.ExecuteScalar();
                totalPembayaranMasuk = result != DBNull.Value ? Convert.ToDecimal(result) : 0m;
            }

            return new
            {
                TotalLunas = totalLunas,
                TotalBelumLunas = totalBelumLunas,
                TotalPembayaranMasuk = totalPembayaranMasuk
            };
        }

        private object GetDashboardSummary(SqlConnection conn, string academicYearId)
        {
            // Total Siswa
            int totalSiswa = 0;
            using (var cmd = new SqlCommand(@"
                SELECT COUNT(DISTINCT p.student_class_id) AS TotalSiswa
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                WHERE ac.academic_year_id = @AcademicYearId
            ", conn))
            {
                cmd.Parameters.AddWithValue("@AcademicYearId", academicYearId ?? (object)DBNull.Value);
                var result = cmd.ExecuteScalar();
                totalSiswa = result != DBNull.Value ? Convert.ToInt32(result) : 0;
            }

            // Total Lunas (siswa yang sudah bayar)
            int totalLunas = 0;
            using (var cmd = new SqlCommand(@"
                SELECT COUNT(DISTINCT p.student_class_id) AS TotalLunas
                FROM txn_payments p
                JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                WHERE ac.academic_year_id = @AcademicYearId 
                AND p.status = 'PAID'
            ", conn))
            {
                cmd.Parameters.AddWithValue("@AcademicYearId", academicYearId ?? (object)DBNull.Value);
                var result = cmd.ExecuteScalar();
                totalLunas = result != DBNull.Value ? Convert.ToInt32(result) : 0;
            }

            // Total Belum Lunas (siswa yang belum bayar sama sekali)
            int totalBelumLunas = 0;
            using (var cmd = new SqlCommand(@"
                SELECT COUNT(*) AS TotalBelumLunas
                FROM (
                    SELECT p.student_class_id
                    FROM txn_payments p
                    JOIN mst_student_classes sc ON p.student_class_id = sc.student_class_id
                    JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                    WHERE ac.academic_year_id = @AcademicYearId
                    GROUP BY p.student_class_id
                    HAVING SUM(CASE WHEN p.status = 'PAID' THEN 1 ELSE 0 END) = 0
                ) AS t
            ", conn))
            {
                cmd.Parameters.AddWithValue("@AcademicYearId", academicYearId ?? (object)DBNull.Value);
                var result = cmd.ExecuteScalar();
                totalBelumLunas = result != DBNull.Value ? Convert.ToInt32(result) : 0;
            }

            return new
            {
                TotalSiswa = totalSiswa,
                TotalLunas = totalLunas,
                TotalBelumLunas = totalBelumLunas
            };
        }

        private List<dynamic> GetAcademicYearsList(SqlConnection conn)
        {
            var list = new List<dynamic>();

            using var cmd = new SqlCommand(@"
                SELECT
                    academic_year_id AS [Value],
                    RIGHT(academic_year_id, LEN(academic_year_id) - CHARINDEX('/', academic_year_id)) AS [Text]
                FROM mst_academic_years;
            ", conn);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    Value = rd["Value"],
                    Text = rd["Text"]
                });
            }

            return list;
        }

        private List<dynamic> GetAcademicClassesList(SqlConnection conn, string academicYear)
        {
            var list = new List<dynamic>();

            using var cmd = new SqlCommand(@"
            SELECT
                academic_class_id AS [Value],
                SUBSTRING(academic_class_id, 4, 2) AS [Text]
            FROM mst_academic_classes WHERE academic_year_id = @academicYear;
            ", conn);

            cmd.Parameters.AddWithValue("@academicYear", academicYear);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    Value = rd["Value"],
                    Text = rd["Text"]
                });
            }

            return list;
        }

        private List<dynamic> GetSubjectList(SqlConnection conn, string? academicClass)
        {
            var list = new List<dynamic>();

            string sql = @"
                    SELECT 
                        subject_id AS [Value],
                        subject_name AS [Text]
                    FROM mst_subjects";

            if (!string.IsNullOrEmpty(academicClass)) 
                sql += " WHERE class_level = SUBSTRING(@academicClass, 4, 1)";

            using var cmd = new SqlCommand(sql, conn);

            if (!string.IsNullOrEmpty(academicClass))
            {
                cmd.Parameters.AddWithValue("@academicClass", academicClass);
            }

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    Value = rd["Value"],
                    Text = rd["Text"]
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
        public IActionResult GetTeacherData(string? classId, string? academicYear, string? academicClass, string? subjectId)
        {
            try
            {
                using var conn = GetConn();
                conn.Open();
                if (string.IsNullOrEmpty(academicYear))
                {
                    academicYear = GetLatestActiveAcademicYear(conn);
                }

                if (string.IsNullOrEmpty(academicYear))
                {
                    return Json(new { success = false, message = "Academic year tidak ditemukan" });
                }
                var teacherId = User.FindFirst("TeacherId")?.Value;

                var data = new
                {
                    academicYearUsed = academicYear,
                    weeklySchedule = GetWeeklySchedule(conn, classId, teacherId),
                    academicYearList = GetAcademicYearsList(conn),
                    academicClassList = GetAcademicClassesList(conn, academicYear),
                    academicSubjectList = GetSubjectList(conn, academicClass),
                    gradesChart = GetGrades(conn, teacherId, academicYear, academicClass, subjectId),
                    attendanceChart = GetAttendance(conn, teacherId, academicYear),
                };

                return Json(new { success = true, data });
            }
            catch (Exception ex)
            {
                return Json(new { success = false, message = ex.Message });
            }
        }

        private List<dynamic> GetWeeklySchedule(SqlConnection conn, string classId, string teacherId)
        {
            var list = new List<dynamic>();

            // Jika tidak ada kelas yang dipilih, kembalikan list kosong (default empty)
            if (string.IsNullOrEmpty(classId))
            {
                return list;
            }

            var sql = @"
<<<<<<< HEAD
        SELECT
            s.day,
            sd.start_time,
            sd.end_time,
            sub.subject_name,
            ISNULL(t.first_name + ' ' + t.last_name, 'Tidak Ditentukan') AS teacher
        FROM mst_schedules s
        JOIN mst_schedule_details sd ON s.schedule_id = sd.schedule_id
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
=======
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
                        WHERE ac.academic_class_id = @classId AND t.teacher_id = @teacherId
                        ORDER BY
                            CASE s.day
                                WHEN 'Senin' THEN 1
                                WHEN 'Selasa' THEN 2
                                WHEN 'Rabu' THEN 3
                                WHEN 'Kamis' THEN 4
                                WHEN 'Jumat' THEN 5
                                WHEN 'Sabtu' THEN 6
                            END, sd.start_time";
>>>>>>> 6477722 (17022026)

            var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@classId", classId);
            cmd.Parameters.AddWithValue("@teacherId", teacherId);

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

        private List<dynamic> GetAttendance(SqlConnection conn, string teacherId, string academicYear)
        {
            var list = new List<dynamic>();
            var sql = @"
                SELECT SUBSTRING(ac.academic_class_id ,4,2) AS [Class], ad.status, COUNT(*) AS count
                FROM txn_attendance_details ad
                JOIN txn_attendances a ON ad.attendance_id = a.attendance_id
                JOIN mst_academic_classes ac ON a.academic_class_id = ac.academic_class_id
                WHERE ac.homeroom_teacher_id = @teacherId AND ac.academic_year_id = @academicYear
                GROUP BY ad.status, ac.academic_class_id ";

            var cmd = new SqlCommand(sql, conn);

            cmd.Parameters.AddWithValue("@teacherId", teacherId);
            cmd.Parameters.AddWithValue("@academicYear", academicYear);

            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                list.Add(new
                {
                    kelas = rd["class"],
                    status = rd["status"],
                    count = (int)rd["count"]
                });
            }
            return list;
        }

        private List<dynamic> GetGrades(SqlConnection conn, string teacherId, string academicYear, string classId, string subjectId)
        {
            var list = new List<dynamic>();

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
            WHERE g.teacher_id = @teacherId 
            AND ac.academic_year_id = @academicYear 
            AND ac.academic_class_id = @classId
            AND g.subject_id = @subjectId
            GROUP BY s.full_name, sub.subject_name ORDER BY s.full_name";

            if(string.IsNullOrEmpty(subjectId)) subjectId = "";

            var cmd = new SqlCommand(sql, conn);
            cmd.Parameters.AddWithValue("@teacherId", teacherId);
            cmd.Parameters.AddWithValue("@academicYear", academicYear);
            cmd.Parameters.AddWithValue("@classId", classId);
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