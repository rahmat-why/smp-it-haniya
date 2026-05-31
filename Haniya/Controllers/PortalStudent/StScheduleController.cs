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
        public class ListSort
        {
            public string field { get; set; } = "day";
            public string order { get; set; } = "asc";
        }

        public class ListRequest
        {
            public int page { get; set; } = 1;
            public int limit { get; set; } = 10;
            public Dictionary<string, string>? filters { get; set; }
            public ListSort? sort { get; set; }
        }

        [HttpPost]
        public IActionResult GetMySchedule([FromBody] ListRequest? req)
        {
            try
            {
                req ??= new ListRequest();
                var page = req.page <= 0 ? 1 : req.page;
                var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);
                var offset = (page - 1) * limit;
                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var search);
                // Ambil student_id dari login
                var studentId = User.FindFirst("StudentId")?.Value;

                if (string.IsNullOrEmpty(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));


                using var conn = GetConn();
                conn.Open();

                var currentClassFilterSql = @"
                      AND sc.student_class_id = (
                            SELECT TOP 1 sc2.student_class_id
                            FROM mst_student_classes sc2
                            JOIN mst_academic_classes ac2 ON sc2.academic_class_id = ac2.academic_class_id
                            JOIN mst_academic_years ay2 ON ac2.academic_year_id = ay2.academic_year_id
                            WHERE sc2.student_id = @studentId
                              AND ay2.status = 'ACTIVE'
                            ORDER BY sc2.student_class_id DESC
                      )";

                var whereSql = @"
                    WHERE sc.student_id = @studentId
                      AND ay.status = 'ACTIVE'
                      AND sch.day IN ('DAY_MON','DAY_TUE','DAY_WED','DAY_THU','DAY_FRI')
                    " + currentClassFilterSql + @"
                      AND (
                            @search IS NULL
                            OR sub.subject_name LIKE @search
                            OR ht.full_name LIKE @search
                            OR c.class_name LIKE @search
                            OR sch.day LIKE @search
                          )";
                var searchPattern = string.IsNullOrWhiteSpace(search) ? null : $"%{search.Trim()}%";
                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["day"] = @"CASE sch.day
                                    WHEN 'DAY_MON' THEN 1
                                    WHEN 'DAY_TUE' THEN 2
                                    WHEN 'DAY_WED' THEN 3
                                    WHEN 'DAY_THU' THEN 4
                                    WHEN 'DAY_FRI' THEN 5
                                    WHEN 'DAY_SAT' THEN 6
                                    WHEN 'DAY_SUN' THEN 7
                                    ELSE 99
                                END",
                    ["time"] = "sd.start_time",
                    ["subject"] = "sub.subject_name",
                    ["teacher"] = "ht.full_name",
                    ["class"] = "c.class_name"
                };
                var sort = req.sort ?? new ListSort();
                var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : sortMap["day"];
                var orderDir = string.Equals(sort.order, "desc", StringComparison.OrdinalIgnoreCase) ? "DESC" : "ASC";

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
                      AND sch.day IN ('DAY_MON','DAY_TUE','DAY_WED','DAY_THU','DAY_FRI')
                    " + currentClassFilterSql;

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
                    cmd.Parameters.AddWithValue("@start", offset);
                    cmd.Parameters.AddWithValue("@length", limit);

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

                var hasNextPage = (offset + list.Count) < recordsFiltered;
                return Json(DTOResponse.ok(new
                {
                    data = list,
                    hasNextPage,
                    totalRows = recordsFiltered,
                    totalAll = recordsTotal
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

    }
}
