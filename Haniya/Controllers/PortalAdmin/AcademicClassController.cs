using Haniya.Models;
using Microsoft.AspNetCore.Mvc;
using System.Data;
using System.Data.SqlClient;
using System.Linq;
using System.Text.RegularExpressions;

namespace Haniya.Controllers.PortalAdmin
{
    public class AcademicClassController : Controller
    {
        private readonly IConfiguration _config;
        public AcademicClassController(IConfiguration config) => _config = config;
        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        public class ListSort
        {
            public string field { get; set; } = "academicYear";
            public string order { get; set; } = "desc";
        }

        public class ListRequest
        {
            public int page { get; set; } = 1;
            public int limit { get; set; } = 10;
            public Dictionary<string, string>? filters { get; set; }
            public ListSort? sort { get; set; }
        }

        [HttpGet]
        public IActionResult Index() => View("~/Views/PortalAdmin/AcademicClass/Index.cshtml");

        [HttpGet]
        public IActionResult Create() => View("~/Views/PortalAdmin/AcademicClass/Create.cshtml");

        [HttpGet]
        public IActionResult Edit(string id)
        {
            ViewBag.academicClassId = id;
            return View("~/Views/PortalAdmin/AcademicClass/Edit.cshtml");
        }

        [HttpPost]
        public IActionResult GetAll([FromBody] ListRequest? req)
        {
            try
            {
                req ??= new ListRequest();
                var page = req.page <= 0 ? 1 : req.page;
                var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);
                var offset = (page - 1) * limit;
                var take = limit + 1; // limit+1 strategy to determine hasNextPage

                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var search);
                filters.TryGetValue("academic_year_id", out var academicYearId);
                filters.TryGetValue("class_level", out var classLevel);

                var sortFieldMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["academicYear"] = "ay.start_date",
                    ["semester"] = "ay.semester",
                    ["className"] = "c.class_name",
                    ["classLevel"] = "c.class_level",
                    ["teacherName"] = "COALESCE(t.first_name,'') + ' ' + COALESCE(t.last_name,'')",
                    ["teacherNpk"] = "t.npk"
                };

                var sort = req.sort ?? new ListSort();
                var orderDir = string.Equals(sort.order, "asc", StringComparison.OrdinalIgnoreCase) ? "ASC" : "DESC";
                var orderBy = sortFieldMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "ay.start_date";

                var where = new List<string>();
                if (!string.IsNullOrWhiteSpace(search))
                {
                    where.Add(@"(
                        c.class_name LIKE @search
                        OR c.class_level LIKE @search
                        OR t.first_name LIKE @search
                        OR t.last_name LIKE @search
                        OR t.npk LIKE @search
                    )");
                }
                if (!string.IsNullOrWhiteSpace(academicYearId)) where.Add("ac.academic_year_id = @academic_year_id");
                if (!string.IsNullOrWhiteSpace(classLevel)) where.Add("c.class_level = @class_level");
                var whereSql = where.Count > 0 ? "WHERE " + string.Join(" AND ", where) : "";

                using var conn = GetConn();
                conn.Open();

                var sql = $@"
                    SELECT
                        ac.academic_class_id,
                        ac.academic_year_id,
                        ay.start_date,
                        ay.end_date,
                        ay.semester,
                        c.class_name,
                        c.class_level,
                        ac.homeroom_teacher_id,
                        t.first_name,
                        t.last_name,
                        t.npk
                    FROM mst_academic_classes ac
                    JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
                    JOIN mst_classes c ON ac.class_id = c.class_id
                    LEFT JOIN mst_teachers t ON ac.homeroom_teacher_id = t.teacher_id
                    {whereSql}
                    ORDER BY {orderBy} {orderDir}
                    OFFSET @offset ROWS FETCH NEXT @take ROWS ONLY";

                var rows = new List<object>();
                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@offset", offset);
                    cmd.Parameters.AddWithValue("@take", take);
                    if (!string.IsNullOrWhiteSpace(search)) cmd.Parameters.AddWithValue("@search", $"%{search.Trim()}%");
                    if (!string.IsNullOrWhiteSpace(academicYearId)) cmd.Parameters.AddWithValue("@academic_year_id", academicYearId.Trim());
                    if (!string.IsNullOrWhiteSpace(classLevel)) cmd.Parameters.AddWithValue("@class_level", classLevel.Trim());

                    using var rd = cmd.ExecuteReader();
                    while (rd.Read())
                    {
                        var fullName = string.Join(" ", new[]
                        {
                            rd["first_name"]?.ToString() ?? "",
                            rd["last_name"]?.ToString() ?? ""
                        }.Where(x => !string.IsNullOrWhiteSpace(x)));

                        rows.Add(new
                        {
                            academic_class_id = rd["academic_class_id"]?.ToString(),
                            academic_year_id = rd["academic_year_id"]?.ToString(),
                            start_date = rd["start_date"] == DBNull.Value ? null : ((DateTime)rd["start_date"]).ToString("yyyy-MM-dd"),
                            end_date = rd["end_date"] == DBNull.Value ? null : ((DateTime)rd["end_date"]).ToString("yyyy-MM-dd"),
                            semester = rd["semester"]?.ToString(),
                            class_name = rd["class_name"]?.ToString(),
                            class_level = rd["class_level"]?.ToString(),
                            homeroom_teacher_id = rd["homeroom_teacher_id"]?.ToString(),
                            homeroom_teacher_name = fullName,
                            homeroom_teacher_npk = rd["npk"]?.ToString()
                        });
                    }
                }

                var hasNextPage = rows.Count > limit;
                if (hasNextPage) rows = rows.Take(limit).ToList();

                return Json(DTOResponse.ok(new { data = rows, hasNextPage }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetLookups()
        {
            try
            {
                using var conn = GetConn();
                conn.Open();

                var years = new List<object>();
                using (var cmd = new SqlCommand(@"
                    SELECT academic_year_id, start_date, end_date, semester, status
                    FROM mst_academic_years
                    ORDER BY start_date DESC, semester DESC", conn))
                using (var rd = cmd.ExecuteReader())
                {
                    while (rd.Read())
                    {
                        years.Add(new
                        {
                            academic_year_id = rd["academic_year_id"]?.ToString(),
                            start_date = rd["start_date"] == DBNull.Value ? null : ((DateTime)rd["start_date"]).ToString("yyyy-MM-dd"),
                            end_date = rd["end_date"] == DBNull.Value ? null : ((DateTime)rd["end_date"]).ToString("yyyy-MM-dd"),
                            semester = rd["semester"]?.ToString(),
                            status = rd["status"]?.ToString()
                        });
                    }
                }

                var classes = new List<object>();
                using (var cmd = new SqlCommand(@"
                    SELECT class_id, class_name, class_level
                    FROM mst_classes
                    ORDER BY class_level, class_name", conn))
                using (var rd = cmd.ExecuteReader())
                {
                    while (rd.Read())
                    {
                        classes.Add(new
                        {
                            class_id = rd["class_id"]?.ToString(),
                            class_name = rd["class_name"]?.ToString(),
                            class_level = rd["class_level"]?.ToString()
                        });
                    }
                }

                var teachers = new List<object>();
                using (var cmd = new SqlCommand(@"
                    SELECT teacher_id, first_name, last_name, npk
                    FROM mst_teachers
                    WHERE status = 'ACTIVE'
                    ORDER BY first_name, last_name", conn))
                using (var rd = cmd.ExecuteReader())
                {
                    while (rd.Read())
                    {
                        var fullName = string.Join(" ", new[]
                        {
                            rd["first_name"]?.ToString() ?? "",
                            rd["last_name"]?.ToString() ?? ""
                        }.Where(x => !string.IsNullOrWhiteSpace(x)));

                        teachers.Add(new
                        {
                            teacher_id = rd["teacher_id"]?.ToString(),
                            full_name = fullName,
                            npk = rd["npk"]?.ToString()
                        });
                    }
                }

                return Json(DTOResponse.ok(new { years, classes, teachers }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetByYear(string academicYearId)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(academicYearId)) return Json(DTOResponse.ok(new List<object>()));

                using var conn = GetConn();
                conn.Open();

                var list = new List<object>();
                using var cmd = new SqlCommand(@"
                    SELECT
                        ac.academic_class_id,
                        ac.class_id,
                        c.class_name,
                        c.class_level,
                        ac.homeroom_teacher_id,
                        t.first_name,
                        t.last_name,
                        t.npk
                    FROM mst_academic_classes ac
                    JOIN mst_classes c ON ac.class_id = c.class_id
                    LEFT JOIN mst_teachers t ON ac.homeroom_teacher_id = t.teacher_id
                    WHERE ac.academic_year_id = @academicYearId", conn);
                cmd.Parameters.AddWithValue("@academicYearId", academicYearId);

                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var teacherName = string.Join(" ", new[]
                    {
                        rd["first_name"]?.ToString() ?? "",
                        rd["last_name"]?.ToString() ?? ""
                    }.Where(x => !string.IsNullOrWhiteSpace(x)));

                    list.Add(new
                    {
                        academic_class_id = rd["academic_class_id"]?.ToString(),
                        class_id = rd["class_id"]?.ToString(),
                        class_name = rd["class_name"]?.ToString(),
                        class_level = rd["class_level"]?.ToString(),
                        homeroom_teacher_id = rd["homeroom_teacher_id"]?.ToString(),
                        homeroom_teacher_name = teacherName,
                        homeroom_teacher_npk = rd["npk"]?.ToString()
                    });
                }

                return Json(DTOResponse.ok(list));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetById(string id)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(id)) return Json(DTOResponse.fail("invalid academic class id", 400));

                using var conn = GetConn();
                conn.Open();
                using var cmd = new SqlCommand(@"
                    SELECT academic_class_id, academic_year_id, class_id, homeroom_teacher_id
                    FROM mst_academic_classes
                    WHERE academic_class_id = @id", conn);
                cmd.Parameters.AddWithValue("@id", id);
                using var rd = cmd.ExecuteReader();

                if (!rd.Read()) return Json(DTOResponse.fail("data not found", 404));
                return Json(DTOResponse.ok(new
                {
                    academic_class_id = rd["academic_class_id"]?.ToString(),
                    academic_year_id = rd["academic_year_id"]?.ToString(),
                    class_id = rd["class_id"]?.ToString(),
                    homeroom_teacher_id = rd["homeroom_teacher_id"]?.ToString()
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Create(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var academicYearId = f["academic_year_id"].ToString();
                var classIds = f["class_ids"];
                var teacherIds = f["homeroom_teacher_ids"];

                if (string.IsNullOrWhiteSpace(academicYearId)) return Json(DTOResponse.fail("academic year is required", 400));
                if (classIds.Count == 0) return Json(DTOResponse.fail("at least one class must be selected", 400));
                if (classIds.Count != teacherIds.Count) return Json(DTOResponse.fail("invalid homeroom teacher data", 400));

                using var conn = GetConn();
                conn.Open();
                using var tran = conn.BeginTransaction();

                var lastCmd = new SqlCommand("SELECT ISNULL(MAX(academic_class_id),'ACC0000') FROM mst_academic_classes", conn, tran);
                var current = ParseTrailingNumber(lastCmd.ExecuteScalar()?.ToString());

                for (int i = 0; i < classIds.Count; i++)
                {
                    var classId = classIds[i]?.Trim();
                    var teacherId = teacherIds[i]?.Trim();
                    if (string.IsNullOrWhiteSpace(classId) || string.IsNullOrWhiteSpace(teacherId)) continue;

                    var checkCmd = new SqlCommand(@"
                        SELECT COUNT(1)
                        FROM mst_academic_classes
                        WHERE academic_year_id = @year AND class_id = @class", conn, tran);
                    checkCmd.Parameters.AddWithValue("@year", academicYearId);
                    checkCmd.Parameters.AddWithValue("@class", classId);
                    var exists = (int)checkCmd.ExecuteScalar() > 0;
                    if (exists) continue;

                    current++;
                    var newId = "ACC" + current.ToString("D4");
                    var insertCmd = new SqlCommand(@"
                        INSERT INTO mst_academic_classes (
                            academic_class_id, academic_year_id, class_id, homeroom_teacher_id, created_at
                        ) VALUES (
                            @id, @year, @class, @teacher, GETDATE()
                        )", conn, tran);
                    insertCmd.Parameters.AddWithValue("@id", newId);
                    insertCmd.Parameters.AddWithValue("@year", academicYearId);
                    insertCmd.Parameters.AddWithValue("@class", classId);
                    insertCmd.Parameters.AddWithValue("@teacher", teacherId);
                    insertCmd.ExecuteNonQuery();
                }

                tran.Commit();
                return Json(DTOResponse.ok(null, "academic classes created"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Update(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var id = f["academic_class_id"].ToString();
                var academicYearId = f["academic_year_id"].ToString();
                var classId = f["class_id"].ToString();
                var teacherId = f["homeroom_teacher_id"].ToString();

                if (string.IsNullOrWhiteSpace(id)) return Json(DTOResponse.fail("invalid academic class id", 400));
                if (string.IsNullOrWhiteSpace(academicYearId) || string.IsNullOrWhiteSpace(classId) || string.IsNullOrWhiteSpace(teacherId))
                    return Json(DTOResponse.fail("all fields are required", 400));

                using var conn = GetConn();
                conn.Open();

                using var checkCmd = new SqlCommand(@"
                    SELECT COUNT(1)
                    FROM mst_academic_classes
                    WHERE academic_year_id = @year
                      AND class_id = @class
                      AND academic_class_id <> @id", conn);
                checkCmd.Parameters.AddWithValue("@year", academicYearId);
                checkCmd.Parameters.AddWithValue("@class", classId);
                checkCmd.Parameters.AddWithValue("@id", id);
                if ((int)checkCmd.ExecuteScalar() > 0) return Json(DTOResponse.fail("academic class already exists for this year and class", 400));

                using var cmd = new SqlCommand(@"
                    UPDATE mst_academic_classes
                    SET academic_year_id = @year,
                        class_id = @class,
                        homeroom_teacher_id = @teacher,
                        updated_at = GETDATE()
                    WHERE academic_class_id = @id", conn);
                cmd.Parameters.AddWithValue("@id", id);
                cmd.Parameters.AddWithValue("@year", academicYearId);
                cmd.Parameters.AddWithValue("@class", classId);
                cmd.Parameters.AddWithValue("@teacher", teacherId);
                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "academic class updated"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpPost]
        public IActionResult Delete([FromBody] DTORequest req)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(req?.id)) return Json(DTOResponse.fail("invalid academic class id", 400));

                using var conn = GetConn();
                conn.Open();
                using var tran = conn.BeginTransaction();

                using (var delSt = new SqlCommand("DELETE FROM mst_student_classes WHERE academic_class_id = @id", conn, tran))
                {
                    delSt.Parameters.AddWithValue("@id", req.id);
                    delSt.ExecuteNonQuery();
                }
                using (var delAc = new SqlCommand("DELETE FROM mst_academic_classes WHERE academic_class_id = @id", conn, tran))
                {
                    delAc.Parameters.AddWithValue("@id", req.id);
                    delAc.ExecuteNonQuery();
                }

                tran.Commit();
                return Json(DTOResponse.ok(null, "academic class deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        private static int ParseTrailingNumber(string? rawId)
        {
            if (string.IsNullOrWhiteSpace(rawId)) return 0;
            var match = Regex.Match(rawId, @"(\d+)$");
            return match.Success && int.TryParse(match.Value, out var n) ? n : 0;
        }
    }
}
