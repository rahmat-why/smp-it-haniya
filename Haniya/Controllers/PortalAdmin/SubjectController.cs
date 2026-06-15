using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Microsoft.AspNetCore.Mvc.Rendering;
using Haniya.Models;

namespace Haniya.Controllers.PortalAdmin
{
    public class SubjectController : Controller
    {
        private readonly IConfiguration _config;

        public SubjectController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        private List<SelectListItem> GetSubjectTypeOptions()
        {
            var items = new List<SelectListItem>();

            using var conn = GetConn();
            conn.Open();

            var sql = @"
                SELECT detail_id, item_desc
                FROM mst_detail_settings
                WHERE header_id = 'SUBJECT_TYPE'
                  AND status = 'ACTIVE'
                ORDER BY item_desc";

            using var cmd = new SqlCommand(sql, conn);
            using var rd = cmd.ExecuteReader();
            while (rd.Read())
            {
                items.Add(new SelectListItem
                {
                    Value = rd["detail_id"]?.ToString(),
                    Text = rd["item_desc"]?.ToString()
                });
            }

            return items;
        }

        /* ===================== PAGES ===================== */

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Subject/Index.cshtml");
        }

        public IActionResult Create()
        {
            ViewBag.SubjectTypes = GetSubjectTypeOptions();
            return View("~/Views/PortalAdmin/Subject/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.subjectId = id;
            ViewBag.SubjectTypes = GetSubjectTypeOptions();
            return View("~/Views/PortalAdmin/Subject/Edit.cshtml");
        }

        /* ===================== API ===================== */
        public class ListSort
        {
            public string field { get; set; } = "subject";
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
        public IActionResult GetAll([FromBody] ListRequest? req)
        {
            try
            {
                req ??= new ListRequest();
                var page = req.page <= 0 ? 1 : req.page;
                var limit = req.limit <= 0 ? 10 : Math.Min(req.limit, 50);

                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var searchValue);

                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["subject"] = "subject_name",
                    ["code"] = "subject_code",
                    ["class"] = "class_level",
                    ["minimumValue"] = "minimum_value",
                    ["description"] = "description"
                };
                var sort = req.sort ?? new ListSort();
                var orderColumn = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "subject_name";
                var orderDirection = string.Equals(sort.order, "desc", StringComparison.OrdinalIgnoreCase) ? "DESC" : "ASC";

                using var conn = GetConn();
                conn.Open();

                // Total records (hanya aktif)
                var totalSql = "SELECT COUNT(*) FROM mst_subjects WHERE status = 'ACTIVE'";
                var totalAll = (int)new SqlCommand(totalSql, conn).ExecuteScalar();

                // Filtered count + search
                var whereClause = "WHERE status = 'ACTIVE'";
                bool hasSearch = !string.IsNullOrWhiteSpace(searchValue);
                string searchPattern = hasSearch ? $"%{searchValue.Trim()}%" : null;

                if (hasSearch)
                {
                    whereClause += @"
                AND (
                    subject_name LIKE @search OR
                    subject_code LIKE @search OR
                    class_level LIKE @search OR
                    description LIKE @search OR
                    CAST(minimum_value AS NVARCHAR(50)) LIKE @search
                )";
                }

                var filteredSql = $"SELECT COUNT(*) FROM mst_subjects {whereClause}";
                using var filteredCmd = new SqlCommand(filteredSql, conn);
                if (hasSearch) filteredCmd.Parameters.AddWithValue("@search", searchPattern);
                var totalRows = (int)filteredCmd.ExecuteScalar();
                var totalPages = totalRows == 0 ? 1 : (int)Math.Ceiling(totalRows / (double)limit);
                page = Math.Min(page, totalPages);
                var offset = (page - 1) * limit;

                // Data query
                var dataSql = $@"
            SELECT 
                subject_id,
                subject_name,
                subject_code,
                class_level,
                description,
                minimum_value,
                CONVERT(varchar(10), created_at, 120) AS created_at
            FROM mst_subjects
            {whereClause}
            ORDER BY {orderColumn} {orderDirection}
                    OFFSET @offset ROWS FETCH NEXT @limit ROWS ONLY";

                using var cmd = new SqlCommand(dataSql, conn);
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@limit", limit);
                if (hasSearch) cmd.Parameters.AddWithValue("@search", searchPattern);

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    list.Add(new
                    {
                        subject_id = rd["subject_id"].ToString(),
                        subject_name = rd["subject_name"]?.ToString() ?? "",
                        subject_code = rd["subject_code"]?.ToString() ?? "",
                        class_level = rd["class_level"]?.ToString() ?? "",
                        description = rd["description"]?.ToString() ?? "",
                        minimum_value = rd["minimum_value"]?.ToString() ?? "",
                        created_at = rd["created_at"]?.ToString() ?? ""
                    });
                }

                var hasNextPage = (offset + list.Count) < totalRows;
                return Json(DTOResponse.ok(new { data = list, hasNextPage, totalRows, totalAll, currentPage = page, limit }));
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
                using var conn = GetConn();
                conn.Open();

                var sql = "SELECT * FROM mst_subjects WHERE subject_id = @id";
                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                return Json(DTOResponse.ok(new
                {
                    subject_id = rd["subject_id"]?.ToString(),
                    subject_name = rd["subject_name"]?.ToString(),
                    subject_code = rd["subject_code"]?.ToString(),
                    subject_type = rd["subject_type"]?.ToString(),
                    class_level = rd["class_level"],
                    description = rd["description"]?.ToString(),
                    minimum_value = rd["minimum_value"]
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

                var subjectName = f["subject_name"].ToString();
                var subjectType = f["subject_type"].ToString();
                var classLevelStr = f["class_level"].ToString();

                if (string.IsNullOrWhiteSpace(subjectName))
                    return Json(DTOResponse.fail("subject name is required", 400));

                if (string.IsNullOrWhiteSpace(subjectType))
                    return Json(DTOResponse.fail("subject type is required", 400));

                if (string.IsNullOrWhiteSpace(classLevelStr))
                    return Json(DTOResponse.fail("class level is required", 400));

                if (!int.TryParse(classLevelStr, out var classLevel))
                    return Json(DTOResponse.fail("class level must be a number", 400));

                var subjectCode = f["subject_code"].ToString();
                var description = f["description"].ToString();
                var minimumValueStr = f["minimum_value"].ToString();
                double? minimumValue = null;

                if (!string.IsNullOrWhiteSpace(minimumValueStr))
                {
                    if (double.TryParse(minimumValueStr, out var mv))
                        minimumValue = mv;
                    else
                        return Json(DTOResponse.fail("minimum value must be a number", 400));
                }

                using var conn = GetConn();
                conn.Open();

                // generate subject_id
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(subject_id),'SUB0000') FROM mst_subjects",
                    conn
                );
                var lastId = lastCmd.ExecuteScalar()?.ToString() ?? "SUB0000";
                var next = int.Parse(lastId.Substring(3)) + 1;
                var subjectId = "SUB" + next.ToString("D4");

                var sql = @"
                    INSERT INTO mst_subjects (
                        subject_id,
                        subject_name,
                        subject_code,
                        subject_type,
                        class_level,
                        description,
                        minimum_value,
                        created_at,
                        status
                    ) VALUES (
                        @id,
                        @name,
                        @code,
                        @type,
                        @level,
                        @desc,
                        @minVal,
                        GETDATE(),
                        @status
                    )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", subjectId);
                cmd.Parameters.AddWithValue("@name", subjectName);
                cmd.Parameters.AddWithValue("@code", (object?)subjectCode ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@type", subjectType);
                cmd.Parameters.AddWithValue("@level", classLevel);
                cmd.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                if (minimumValue.HasValue)
                    cmd.Parameters.AddWithValue("@minVal", minimumValue.Value);
                else
                    cmd.Parameters.AddWithValue("@minVal", DBNull.Value);
                cmd.Parameters.AddWithValue("@status", "ACTIVE");
                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "subject created"));
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
                var subjectId = f["subject_id"].ToString();

                if (string.IsNullOrWhiteSpace(subjectId))
                    return Json(DTOResponse.fail("invalid subject id", 400));

                var subjectName = f["subject_name"].ToString();
                var subjectType = f["subject_type"].ToString();
                var classLevelStr = f["class_level"].ToString();

                if (string.IsNullOrWhiteSpace(subjectName))
                    return Json(DTOResponse.fail("subject name is required", 400));

                if (string.IsNullOrWhiteSpace(subjectType))
                    return Json(DTOResponse.fail("subject type is required", 400));

                if (string.IsNullOrWhiteSpace(classLevelStr))
                    return Json(DTOResponse.fail("class level is required", 400));

                if (!int.TryParse(classLevelStr, out var classLevel))
                    return Json(DTOResponse.fail("class level must be a number", 400));

                var subjectCode = f["subject_code"].ToString();
                var description = f["description"].ToString();
                var minimumValueStr = f["minimum_value"].ToString();
                double? minimumValue = null;

                if (!string.IsNullOrWhiteSpace(minimumValueStr))
                {
                    if (double.TryParse(minimumValueStr, out var mv))
                        minimumValue = mv;
                    else
                        return Json(DTOResponse.fail("minimum value must be a number", 400));
                }

                using var conn = GetConn();
                conn.Open();

                var sql = @"
                    UPDATE mst_subjects SET
                        subject_name = @name,
                        subject_code = @code,
                        subject_type = @type,
                        class_level = @level,
                        description = @desc,
                        minimum_value = @minVal,
                        updated_at = GETDATE()
                    WHERE subject_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", subjectId);
                cmd.Parameters.AddWithValue("@name", subjectName);
                cmd.Parameters.AddWithValue("@code", (object?)subjectCode ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@type", subjectType);
                cmd.Parameters.AddWithValue("@level", classLevel);
                cmd.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                if (minimumValue.HasValue)
                    cmd.Parameters.AddWithValue("@minVal", minimumValue.Value);
                else
                    cmd.Parameters.AddWithValue("@minVal", DBNull.Value);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "subject updated"));
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
                if (string.IsNullOrEmpty(req?.id))
                    return Json(DTOResponse.fail("invalid subject id", 400));

                using var conn = GetConn();
                conn.Open();

                var cmd = new SqlCommand(
                    "DELETE FROM mst_subjects WHERE subject_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "subject deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
