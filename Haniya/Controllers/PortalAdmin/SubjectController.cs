using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
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

        /* ===================== PAGES ===================== */

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Subject/Index.cshtml");
        }

        public IActionResult Create()
        {
            return View("~/Views/PortalAdmin/Subject/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.subjectId = id;
            return View("~/Views/PortalAdmin/Subject/Edit.cshtml");
        }

        /* ===================== API ===================== */
        private (int draw, int start, int length, string searchValue, string orderColumn, string orderDir)
            ParseDataTablesQuery(string[] columns)
        {
            var q = Request.Query;
            int.TryParse(q["draw"], out var draw);
            if (draw <= 0) draw = 1;
            int.TryParse(q["start"], out var start);
            if (start < 0) start = 0;
            int.TryParse(q["length"], out var length);
            if (length <= 0) length = 10;
            var searchValue = q["search[value]"].ToString() ?? string.Empty;
            var orderColumn = "subject_name"; // default
            var orderDir = "ASC";

            var orderColIdxStr = q["order[0][column]"].ToString();
            if (int.TryParse(orderColIdxStr, out var orderColIdx))
            {
                if (orderColIdx >= 0 && orderColIdx < columns.Length)
                    orderColumn = columns[orderColIdx];
            }

            var dir = q["order[0][dir]"].ToString();
            if (!string.IsNullOrWhiteSpace(dir) &&
                (dir.Equals("asc", StringComparison.OrdinalIgnoreCase) ||
                 dir.Equals("desc", StringComparison.OrdinalIgnoreCase)))
            {
                orderDir = dir.ToUpper();
            }

            return (draw, start, length, searchValue, orderColumn, orderDir);
        }

        [HttpGet]
        public IActionResult GetAll()
        {
            try
            {
                var columns = new[]
                {
            "subject_name",
            "subject_code",
            "class_level",
            "minimum_value",
            "description",
            "created_at"
        };

                var (draw, start, length, searchValue, orderColumn, orderDir) = ParseDataTablesQuery(columns);

                using var conn = GetConn();
                conn.Open();

                // Total records
                var totalCmd = new SqlCommand(
                    "SELECT COUNT(*) FROM mst_subjects",
                    conn
                );
                var recordsTotal = (int)totalCmd.ExecuteScalar();

                // Filtered count
                string whereSearch = "";
                if (!string.IsNullOrWhiteSpace(searchValue))
                {
                    whereSearch = @" WHERE (
                subject_name LIKE @search OR
                subject_code LIKE @search OR
                class_level LIKE @search OR
                description LIKE @search OR
                CAST(minimum_value AS NVARCHAR) LIKE @search
            )";
                }

                var filteredCmd = new SqlCommand(
                    "SELECT COUNT(*) FROM mst_subjects" + whereSearch,
                    conn
                );
                if (!string.IsNullOrWhiteSpace(searchValue))
                    filteredCmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                var recordsFiltered = (int)filteredCmd.ExecuteScalar();

                // Data query
                var sql = $@"
            SELECT
                subject_id,
                subject_name,
                subject_code,
                class_level,
                description,
                minimum_value,
                created_at
            FROM mst_subjects
            {whereSearch}
            ORDER BY {orderColumn} {orderDir}
            OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    cmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    list.Add(new
                    {
                        subject_id = rd["subject_id"],
                        subject_name = rd["subject_name"],
                        subject_code = rd["subject_code"],
                        class_level = rd["class_level"],
                        description = rd["description"],
                        minimum_value = rd["minimum_value"],
                        created_at = rd["created_at"]
                    });
                }

                return Json(new
                {
                    draw,
                    recordsTotal,
                    recordsFiltered,
                    data = list
                });
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
                var classLevelStr = f["class_level"].ToString();

                if (string.IsNullOrWhiteSpace(subjectName))
                    return Json(DTOResponse.fail("subject name is required", 400));

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
                        class_level,
                        description,
                        minimum_value,
                        created_at
                    ) VALUES (
                        @id,
                        @name,
                        @code,
                        @level,
                        @desc,
                        @minVal,
                        GETDATE()
                    )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", subjectId);
                cmd.Parameters.AddWithValue("@name", subjectName);
                cmd.Parameters.AddWithValue("@code", (object?)subjectCode ?? DBNull.Value);
                cmd.Parameters.AddWithValue("@level", classLevel);
                cmd.Parameters.AddWithValue("@desc", (object?)description ?? DBNull.Value);
                if (minimumValue.HasValue)
                    cmd.Parameters.AddWithValue("@minVal", minimumValue.Value);
                else
                    cmd.Parameters.AddWithValue("@minVal", DBNull.Value);

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
                var classLevelStr = f["class_level"].ToString();

                if (string.IsNullOrWhiteSpace(subjectName))
                    return Json(DTOResponse.fail("subject name is required", 400));

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
                        class_level = @level,
                        description = @desc,
                        minimum_value = @minVal,
                        updated_at = GETDATE()
                    WHERE subject_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", subjectId);
                cmd.Parameters.AddWithValue("@name", subjectName);
                cmd.Parameters.AddWithValue("@code", (object?)subjectCode ?? DBNull.Value);
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