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

        private (int draw, int start, int length, string searchValue, int orderColumnIndex, string orderDir) ParseDataTablesQuery()
        {
            var form = Request.HasFormContentType ? Request.Form : null;
            var q = Request.Query;

            string GetVal(string key)
            {
                if (form != null && form.ContainsKey(key)) return form[key].ToString();
                return q[key].ToString();
            }

            int.TryParse(GetVal("draw"), out var draw);
            if (draw <= 0) draw = 1;
            int.TryParse(GetVal("start"), out var start);
            if (start < 0) start = 0;
            int.TryParse(GetVal("length"), out var length);
            if (length <= 0) length = 10;
            var searchValue = GetVal("search[value]") ?? string.Empty;
            int.TryParse(GetVal("order[0][column]"), out var orderColumnIndex);
            var rawDir = (GetVal("order[0][dir]") ?? "").ToUpper();
            var orderDir = rawDir is "ASC" or "DESC" ? rawDir : "ASC";

            return (draw, start, length, searchValue, orderColumnIndex, orderDir);
        }

        private string GetScheduleOrderByColumn(int orderColumnIndex)
        {
            return orderColumnIndex switch
            {
                0 => @"CASE sch.day
                            WHEN 'DAY_MON' THEN 1
                            WHEN 'DAY_TUE' THEN 2
                            WHEN 'DAY_WED' THEN 3
                            WHEN 'DAY_THU' THEN 4
                            WHEN 'DAY_FRI' THEN 5
                            WHEN 'DAY_SAT' THEN 6
                            WHEN 'DAY_SUN' THEN 7
                            ELSE 99
                        END",
                1 => "sd.start_time",
                2 => "sub.subject_name",
                3 => "ht.full_name",
                4 => "c.class_name",
                _ => "sd.start_time"
            };
        }

        [HttpPost]
        public IActionResult GetMySchedule()
        {
            try
            {
                var (draw, start, length, search, orderColumnIndex, orderDir) = ParseDataTablesQuery();
                // Ambil student_id dari login
                var studentId = User.FindFirst("StudentId")?.Value;

                if (string.IsNullOrEmpty(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));


                using var conn = GetConn();
                conn.Open();

                var whereSql = @"
                    WHERE sc.student_id = @studentId
                      AND ay.status = 'ACTIVE'
                      AND sch.day IN ('DAY_MON','DAY_TUE','DAY_WED','DAY_THU','DAY_FRI')
                      AND (
                            @search IS NULL
                            OR sub.subject_name LIKE @search
                            OR ht.full_name LIKE @search
                            OR c.class_name LIKE @search
                            OR sch.day LIKE @search
                          )";
                var searchPattern = string.IsNullOrWhiteSpace(search) ? null : $"%{search.Trim()}%";
                var orderBy = GetScheduleOrderByColumn(orderColumnIndex);

                var totalSql = @"
                    SELECT COUNT(*)
                    FROM mst_student_classes sc
                    JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                    JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
                    JOIN mst_schedules sch ON sch.academic_class_id = ac.academic_class_id
                    JOIN mst_schedule_details sd ON sd.schedule_id = sch.schedule_id
                    JOIN mst_subjects sub ON sd.subject_id = sub.subject_id
                    LEFT JOIN mst_teachers ht ON sd.teacher_id = ht.teacher_id
                    JOIN mst_classes c ON ac.class_id = c.class_id
                    WHERE sc.student_id = @studentId
                      AND ay.status = 'ACTIVE'
                      AND sch.day IN ('DAY_MON','DAY_TUE','DAY_WED','DAY_THU','DAY_FRI')";

                var filteredSql = @"
                    SELECT COUNT(*)
                    FROM mst_student_classes sc
                    JOIN mst_academic_classes ac ON sc.academic_class_id = ac.academic_class_id
                    JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
                    JOIN mst_schedules sch ON sch.academic_class_id = ac.academic_class_id
                    JOIN mst_schedule_details sd ON sd.schedule_id = sch.schedule_id
                    JOIN mst_subjects sub ON sd.subject_id = sub.subject_id
                    LEFT JOIN mst_teachers ht ON sd.teacher_id = ht.teacher_id
                    JOIN mst_classes c ON ac.class_id = c.class_id
                    " + whereSql;

                int recordsTotal;
                using (var totalCmd = new SqlCommand(totalSql, conn))
                {
                    totalCmd.Parameters.AddWithValue("@studentId", studentId);
                    recordsTotal = Convert.ToInt32(totalCmd.ExecuteScalar() ?? 0);
                }

                int recordsFiltered;
                using (var filteredCmd = new SqlCommand(filteredSql, conn))
                {
                    filteredCmd.Parameters.AddWithValue("@studentId", studentId);
                    filteredCmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                    recordsFiltered = Convert.ToInt32(filteredCmd.ExecuteScalar() ?? 0);
                }

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
                        ON sd.teacher_id = ht.teacher_id
                    JOIN mst_classes c
                        ON ac.class_id = c.class_id
                    " + whereSql + @"
                    ORDER BY " + orderBy + " " + orderDir + @",
                        sd.start_time
                    OFFSET @start ROWS FETCH NEXT @length ROWS ONLY
                    ";

                var list = new List<object>();

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", studentId);
                    cmd.Parameters.AddWithValue("@search", (object)searchPattern ?? DBNull.Value);
                    cmd.Parameters.AddWithValue("@start", start);
                    cmd.Parameters.AddWithValue("@length", length);

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

                return Json(new { draw, recordsTotal, recordsFiltered, data = list });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

    }
}
