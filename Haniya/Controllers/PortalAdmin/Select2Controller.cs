using Haniya.Models;
using Microsoft.AspNetCore.Http.HttpResults;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Configuration;
using System.Data.SqlClient;
using static System.Runtime.InteropServices.JavaScript.JSType;
using System.Reflection;

namespace Haniya.Controllers
{
    public class Select2Controller : Controller
    {
        private readonly IConfiguration _config;

        public Select2Controller(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        [HttpGet]
        public IActionResult Gender(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'GENDER'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND item_desc LIKE @q";
                }

                // total count untuk pagination.more
                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");

                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                // NOTE: CAST item_desc agar bisa di-ORDER BY
                var sql = $@"
            SELECT item_name, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY CAST(item_desc AS NVARCHAR(4000))
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    results.Add(new
                    {
                        id = rd["item_name"]?.ToString(),
                        text = rd["item_desc"]?.ToString()
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult LevelStudent(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'LEVEL_STUDENT'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND item_desc LIKE @q";
                }

                // total count untuk pagination.more
                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");

                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                // NOTE: CAST item_desc agar bisa di-ORDER BY
                var sql = $@"
            SELECT detail_id, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY CAST(item_desc AS NVARCHAR(4000))
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    results.Add(new
                    {
                        id = rd["detail_id"]?.ToString(),
                        text = rd["item_desc"]?.ToString()
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult Employment(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'EMPLOYMENT'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND item_desc LIKE @q";
                }

                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");

                var total = (int)countCmd.ExecuteScalar();
                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT detail_id, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY CAST(item_desc AS NVARCHAR(4000))
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    results.Add(new
                    {
                        id = rd["detail_id"]?.ToString(),
                        text = rd["item_desc"]?.ToString()
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult TagsArticle(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'TAG_ARTICLE'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND (item_code LIKE @q OR item_name LIKE @q OR item_desc LIKE @q)";
                }

                // Total count for pagination.more
                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT item_code, item_name, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY item_name
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var name = rd["item_name"]?.ToString() ?? "";
                    var code = rd["item_code"]?.ToString() ?? "";
                    var desc = rd["item_desc"]?.ToString() ?? "";

                    var display = desc;

                    results.Add(new
                    {
                        id = code,
                        text = display
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult TagsEvent(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'TAG_EVENT'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND (item_code LIKE @q OR item_name LIKE @q OR item_desc LIKE @q)";
                }

                // Total count for pagination.more
                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT item_code, item_name, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY item_name
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var name = rd["item_name"]?.ToString() ?? "";
                    var code = rd["item_code"]?.ToString() ?? "";
                    var desc = rd["item_desc"]?.ToString() ?? "";

                    var display = desc;

                    results.Add(new
                    {
                        id = code,
                        text = display
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult AcademicYears(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND (CAST(start_date AS NVARCHAR) LIKE @q OR CAST(end_date AS NVARCHAR) LIKE @q)";
                }

                // Total count
                var countSql = $"SELECT COUNT(*) FROM mst_academic_years {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT 
                academic_year_id,
                start_date,
                end_date,
                semester,
                status
            FROM mst_academic_years
            {where}
            ORDER BY start_date DESC
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var startYear = Convert.ToDateTime(rd["start_date"]).Year;
                    var endYear = Convert.ToDateTime(rd["end_date"]).Year;
                    var display = $"{startYear} - {endYear} (Semester {rd["semester"] ?? "?"})";

                    results.Add(new
                    {
                        id = rd["academic_year_id"].ToString(),
                        text = display
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult Classes(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE 1=1";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND (class_name LIKE @q OR class_level LIKE @q)";
                }

                var countSql = $"SELECT COUNT(*) FROM mst_classes {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT class_id, class_name, class_level
            FROM mst_classes
            {where}
            ORDER BY class_level, class_name
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var name = rd["class_name"]?.ToString() ?? "";
                    var level = rd["class_level"]?.ToString() ?? "";
                    var display = $"{name} ({level})";

                    results.Add(new
                    {
                        id = rd["class_id"].ToString(),
                        text = display
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult Teachers(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND (first_name LIKE @q OR last_name LIKE @q OR npk LIKE @q)";
                }

                var countSql = $"SELECT COUNT(*) FROM mst_teachers {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT teacher_id, first_name, last_name, npk
            FROM mst_teachers
            {where}
            ORDER BY first_name, last_name
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var first = rd["first_name"]?.ToString() ?? "";
                    var last = rd["last_name"]?.ToString() ?? "";
                    var full = string.Join(" ", new[] { first, last }.Where(s => !string.IsNullOrWhiteSpace(s)));
                    var npk = rd["npk"]?.ToString() ?? "";
                    var display = full + " - " + npk;

                    results.Add(new
                    {
                        id = rd["teacher_id"].ToString(),
                        text = display
                    });
                }

                // This line is correct: more is bool
                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }  // more is bool → correct
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult AttendanceStatus(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'ATTENDANCE_STATUS'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND item_desc LIKE @q";
                }

                // Count without sorting
                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                // Use CAST to nvarchar for sorting (safe workaround)
                var sql = $@"
            SELECT detail_id, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY CAST(item_desc AS nvarchar(4000))
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    results.Add(new
                    {
                        id = rd["detail_id"].ToString(),
                        text = rd["item_desc"]?.ToString() ?? ""
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult AcademicClasses(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE 1=1";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND (c.class_name LIKE @q OR CAST(ay.start_date AS NVARCHAR) LIKE @q OR CAST(ay.end_date AS NVARCHAR) LIKE @q)";
                }

                var countSql = $@"
            SELECT COUNT(*) 
            FROM mst_academic_classes ac
            JOIN mst_classes c ON ac.class_id = c.class_id
            JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
            {where}";

                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT 
                ac.academic_class_id,
                c.class_name,
                ay.start_date,
                ay.end_date,
                ay.semester
            FROM mst_academic_classes ac
            JOIN mst_classes c ON ac.class_id = c.class_id
            JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
            {where}
            ORDER BY ay.start_date DESC, c.class_name
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var startYear = Convert.ToDateTime(rd["start_date"]).Year;
                    var endYear = Convert.ToDateTime(rd["end_date"]).Year;
                    var display = $"{rd["class_name"]}";

                    results.Add(new
                    {
                        id = rd["academic_class_id"].ToString(),
                        text = display
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult Subjects(string classLevel = null, string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE'";
                var parameters = new Dictionary<string, object>();

                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND (subject_name LIKE @q OR class_level LIKE @q)";
                    parameters["@q"] = $"%{q}%";
                }

                if (!string.IsNullOrEmpty(classLevel))
                {
                    where += " AND class_level = @classLevel";
                    parameters["@classLevel"] = classLevel;
                }

                var countSql = $"SELECT COUNT(*) FROM mst_subjects {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                foreach (var p in parameters)
                    countCmd.Parameters.AddWithValue(p.Key, p.Value);
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;
                var sql = $@"
            SELECT subject_id, subject_name, class_level
            FROM mst_subjects
            {where}
            ORDER BY subject_name
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                foreach (var p in parameters)
                    cmd.Parameters.AddWithValue(p.Key, p.Value);
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var subjectName = rd["subject_name"]?.ToString() ?? "";
                    var subjectClassLevel = rd["class_level"]?.ToString() ?? "";
                    var text = string.IsNullOrWhiteSpace(subjectClassLevel)
                        ? subjectName
                        : $"{subjectName} - Class {subjectClassLevel}";

                    results.Add(new
                    {
                        id = rd["subject_id"].ToString(),
                        text
                    });
                }

                var more = (page * pageSize) < total;
                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GradeTypes(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'GRADE_TYPE'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND item_desc LIKE @q";
                }

                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT detail_id, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY CAST(item_desc AS NVARCHAR(4000))
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    results.Add(new
                    {
                        id = rd["detail_id"].ToString(),
                        text = rd["item_desc"]?.ToString()
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        // Add this method to your existing Select2Controller.cs

        [HttpGet]
        public IActionResult Days(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'DAY'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND item_desc LIKE @q";
                }

                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");

                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT item_name, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY CAST(item_desc AS NVARCHAR(4000))
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    results.Add(new
                    {
                        id = rd["item_name"]?.ToString(),
                        text = rd["item_desc"]?.ToString()
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult PaymentTypes(string q = "", int page = 1)
        {
            using var conn = GetConn();
            conn.Open();

            var sql = @"
        SELECT detail_id AS id, item_desc AS text
        FROM mst_detail_settings
        WHERE header_id = 'PAYMENT_TYPE'
          AND (@q IS NULL OR item_desc LIKE @pattern)
        ORDER BY item_desc
        OFFSET @offset ROWS FETCH NEXT 10 ROWS ONLY";

            var results = new List<object>();
            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@q", string.IsNullOrWhiteSpace(q) ? DBNull.Value : q);
                cmd.Parameters.AddWithValue("@pattern", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", (page - 1) * 10);

                using var r = cmd.ExecuteReader();
                while (r.Read())
                {
                    results.Add(new { id = r["id"].ToString(), text = r["text"].ToString() });
                }
            }

            return Json(new { results, pagination = new { more = results.Count == 10 } });
        }

        [HttpGet]
        public IActionResult PaymentMethods(string q = "", int page = 1)
        {
            using var conn = GetConn();
            conn.Open();

            var sql = @"
        SELECT detail_id AS id, item_desc AS text
        FROM mst_detail_settings
        WHERE header_id = 'PAYMENT_METHOD'
          AND (@q IS NULL OR item_desc LIKE @pattern)
        ORDER BY item_desc
        OFFSET @offset ROWS FETCH NEXT 10 ROWS ONLY";

            var results = new List<object>();
            using (var cmd = new SqlCommand(sql, conn))
            {
                cmd.Parameters.AddWithValue("@q", string.IsNullOrWhiteSpace(q) ? DBNull.Value : q);
                cmd.Parameters.AddWithValue("@pattern", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", (page - 1) * 10);

                using var r = cmd.ExecuteReader();
                while (r.Read())
                {
                    results.Add(new { id = r["id"].ToString(), text = r["text"].ToString() });
                }
            }

            return Json(new { results, pagination = new { more = results.Count == 10 } });
        }

        [HttpGet]
        public IActionResult Students(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE st.status = 'ACTIVE'";
                var hasSearch = !string.IsNullOrWhiteSpace(q);

                // Hitung total
                var countSql = $@"
            SELECT COUNT(*) 
            FROM mst_student_classes sc
            INNER JOIN mst_students st ON sc.student_id = st.student_id
            {where}";

                if (hasSearch)
                {
                    countSql += " AND (st.full_name LIKE @q OR st.first_name LIKE @q OR st.last_name LIKE @q OR st.nis LIKE @q)";
                }

                int total;
                using (var countCmd = new SqlCommand(countSql, conn))
                {
                    if (hasSearch)
                    {
                        countCmd.Parameters.AddWithValue("@q", $"%{q}%");
                    }
                    total = (int)countCmd.ExecuteScalar();
                }

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT 
                sc.student_class_id,
                st.student_id,
                st.nis,
                COALESCE(st.full_name, CONCAT(st.first_name, ' ', st.last_name)) AS student_name
            FROM mst_student_classes sc
            INNER JOIN mst_students st ON sc.student_id = st.student_id
            {where}";

                if (hasSearch)
                {
                    sql += " AND (st.full_name LIKE @q OR st.first_name LIKE @q OR st.last_name LIKE @q OR st.nis LIKE @q)";
                }

                sql += @"
            ORDER BY st.full_name, st.nis
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                var results = new List<object>();
                using (var cmd = new SqlCommand(sql, conn))
                {
                    if (hasSearch)
                    {
                        cmd.Parameters.AddWithValue("@q", $"%{q}%");
                    }
                    cmd.Parameters.AddWithValue("@offset", offset);
                    cmd.Parameters.AddWithValue("@pageSize", pageSize);

                    using var rd = cmd.ExecuteReader();
                    while (rd.Read())
                    {
                        var name = rd["student_name"]?.ToString() ?? "";
                        var nis = rd["nis"]?.ToString() ?? "-";
                        var display = $"{name} - {nis}";

                        results.Add(new
                        {
                            id = rd["student_class_id"].ToString(),
                            text = display
                        });
                    }
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult RPSActivities(string q = "", int page = 1, int pageSize = 20)
        {
            try
            {
                if (page <= 0) page = 1;
                if (pageSize <= 0) pageSize = 20;

                using var conn = GetConn();
                conn.Open();

                var where = "WHERE status = 'ACTIVE' AND header_id = 'RPS_ACTIVITY'";
                if (!string.IsNullOrWhiteSpace(q))
                {
                    where += " AND item_desc LIKE @q";
                }

                var countSql = $"SELECT COUNT(*) FROM mst_detail_settings {where}";
                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    countCmd.Parameters.AddWithValue("@q", $"%{q}%");

                var total = (int)countCmd.ExecuteScalar();

                var offset = (page - 1) * pageSize;

                var sql = $@"
            SELECT detail_id, item_desc
            FROM mst_detail_settings
            {where}
            ORDER BY CAST(item_desc AS NVARCHAR(4000))
            OFFSET @offset ROWS FETCH NEXT @pageSize ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                if (!string.IsNullOrWhiteSpace(q))
                    cmd.Parameters.AddWithValue("@q", $"%{q}%");
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@pageSize", pageSize);

                var results = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    results.Add(new
                    {
                        id = rd["detail_id"]?.ToString(),
                        text = rd["item_desc"]?.ToString()
                    });
                }

                var more = (page * pageSize) < total;

                return Json(new
                {
                    results,
                    pagination = new { more }
                });
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
