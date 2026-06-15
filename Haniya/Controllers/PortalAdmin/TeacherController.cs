using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Haniya.Models;
using Newtonsoft.Json.Linq;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using Microsoft.AspNetCore.Authorization;

namespace Haniya.Controllers.PortalAdmin
{
    [Authorize]
    public class TeacherController : Controller
    {
        private readonly IConfiguration _config;

        public TeacherController(IConfiguration config)
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
            return View("~/Views/PortalAdmin/Teacher/Index.cshtml");
        }

        public IActionResult Create()
        {
            return View("~/Views/PortalAdmin/Teacher/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.teacherId = id;
            return View("~/Views/PortalAdmin/Teacher/Edit.cshtml");
        }

        /* ===================== API ===================== */

        public class ListSort
        {
            public string field { get; set; } = "teacher";
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
                filters.TryGetValue("search", out var search);
                filters.TryGetValue("gender", out var gender);
                filters.TryGetValue("employment", out var employment);

                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["teacher"] = "t.first_name",
                    ["gender"] = "dg.item_desc",
                    ["birthDate"] = "t.birth_date",
                    ["birthPlace"] = "t.birth_place",
                    ["address"] = "t.address",
                    ["phone"] = "t.phone",
                    ["entryDate"] = "t.entry_date",
                    ["employment"] = "t.level"
                };
                var sort = req.sort ?? new ListSort();
                var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "t.first_name";
                var orderDir = string.Equals(sort.order, "desc", StringComparison.OrdinalIgnoreCase) ? "DESC" : "ASC";

                using var conn = GetConn();
                conn.Open();

                var where = new List<string> { "t.status = 'ACTIVE'" };
                if (!string.IsNullOrWhiteSpace(search))
                {
                    where.Add(@"(
                        t.npk LIKE @search OR
                        t.first_name LIKE @search OR
                        t.last_name LIKE @search OR
                        t.address LIKE @search OR
                        t.phone LIKE @search OR
                        t.level LIKE @search
                    )");
                }
                if (!string.IsNullOrWhiteSpace(gender)) where.Add("t.gender = @gender");
                if (!string.IsNullOrWhiteSpace(employment)) where.Add("t.level = @employment");
                var whereSql = "WHERE " + string.Join(" AND ", where);

                var countSql = $@"
                    SELECT COUNT(1)
                    FROM mst_teachers t
                    LEFT JOIN mst_detail_settings dg
                        ON dg.item_code = t.gender
                        AND dg.header_id = 'GENDER'
                        AND dg.status = 'ACTIVE'
                    {whereSql}";

                using var countCmd = new SqlCommand(countSql, conn);
                if (!string.IsNullOrWhiteSpace(search)) countCmd.Parameters.AddWithValue("@search", $"%{search.Trim()}%");
                if (!string.IsNullOrWhiteSpace(gender)) countCmd.Parameters.AddWithValue("@gender", gender.Trim());
                if (!string.IsNullOrWhiteSpace(employment)) countCmd.Parameters.AddWithValue("@employment", employment.Trim());
                var totalRows = Convert.ToInt32(countCmd.ExecuteScalar() ?? 0);
                var totalPages = totalRows == 0 ? 1 : (int)Math.Ceiling(totalRows / (double)limit);
                page = Math.Min(page, totalPages);
                var offset = (page - 1) * limit;

                var sql = $@"
                    SELECT
                        t.teacher_id,
                        t.npk,
                        t.first_name,
                        t.last_name,
                        dg.item_desc AS gender,
                        t.birth_place,
                        t.birth_date,
                        t.profile_photo,
                        t.address,
                        t.phone,
                        t.entry_date,
                        t.level
                    FROM mst_teachers t
                    LEFT JOIN mst_detail_settings dg
                        ON dg.item_code = t.gender
                        AND dg.header_id = 'GENDER'
                        AND dg.status = 'ACTIVE'
                    {whereSql}
                    ORDER BY {orderBy} {orderDir}
                    OFFSET @offset ROWS FETCH NEXT @limit ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@limit", limit);
                if (!string.IsNullOrWhiteSpace(search)) cmd.Parameters.AddWithValue("@search", $"%{search.Trim()}%");
                if (!string.IsNullOrWhiteSpace(gender)) cmd.Parameters.AddWithValue("@gender", gender.Trim());
                if (!string.IsNullOrWhiteSpace(employment)) cmd.Parameters.AddWithValue("@employment", employment.Trim());

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var fullName = string.Join(" ",
                        rd["first_name"], rd["last_name"]);

                    list.Add(new
                    {
                        teacher_id = rd["teacher_id"],
                        full_name = fullName.Trim(),
                        npk = rd["npk"],
                        gender = rd["gender"],
                        birth_place = rd["birth_place"],
                        birth_date = rd["birth_date"],
                        profile_photo = rd["profile_photo"],
                        address = rd["address"],
                        phone = rd["phone"],
                        entry_date = rd["entry_date"],
                        level = rd["level"]
                    });
                }

                var hasNextPage = (offset + list.Count) < totalRows;

                return Json(DTOResponse.ok(new { data = list, hasNextPage, totalRows, currentPage = page, limit }));
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

                var sql = @"
                    SELECT t.*, dg.item_desc AS gender_name
                    FROM mst_teachers t
                    LEFT JOIN mst_detail_settings dg
                        ON dg.item_code = t.gender
                        AND dg.header_id = 'GENDER'
                        AND dg.status = 'ACTIVE'
                    WHERE t.teacher_id = @id";
                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                var first = rd["first_name"]?.ToString() ?? "";
                var last = rd["last_name"]?.ToString() ?? "";
                var full = string.Join(" ", new[] { first, last }.Where(s => !string.IsNullOrWhiteSpace(s)));

                return Json(DTOResponse.ok(new
                {
                    teacher_id = rd["teacher_id"]?.ToString(),
                    first_name = first,
                    last_name = last,
                    full_name = full,
                    npk = rd["npk"]?.ToString(),

                    birth_date = rd["birth_date"] == DBNull.Value
                        ? null
                        : ((DateTime)rd["birth_date"]).ToString("yyyy-MM-dd"),
                    birth_place = rd["birth_place"]?.ToString(),
                    gender = rd["gender"]?.ToString(),
                    gender_name = rd["gender_name"]?.ToString(),
                    address = rd["address"]?.ToString(),
                    phone = rd["phone"]?.ToString(),
                    entry_date = rd["entry_date"] == DBNull.Value
                        ? null
                        : ((DateTime)rd["entry_date"]).ToString("yyyy-MM-dd"),

                    level = rd["level"]?.ToString(),
                    status = rd["status"]?.ToString(),

                    profile_photo = rd["profile_photo"]?.ToString()
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
                var file = Request.Form.Files["profile_photo"];

                using var conn = GetConn();
                conn.Open();

                /* ===== generate teacher_id (TCH0001, TCH0002, ...) ===== */
                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(teacher_id),'TCH0000') FROM mst_teachers",
                    conn
                );
                var lastId = lastCmd.ExecuteScalar()?.ToString() ?? "TCH0000";
                var next = int.Parse(lastId.Substring(3)) + 1;
                var teacherId = "TCH" + next.ToString("D4");

                /* ===== upload photo ===== */
                string photoPath = null;
                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/teacher"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = teacherId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    photoPath = "/image/teacher/" + fileName;
                }

                /* ===== insert ===== */
                var sql = @"
                    INSERT INTO mst_teachers (
                        teacher_id,
                        first_name,
                        last_name,
                        npk,
                        gender,
                        birth_place,
                        birth_date,
                        profile_photo,
                        address,
                        phone,
                        entry_date,
                        password,
                        level,
                        status,
                        created_at
                    ) VALUES (
                        @id,
                        @fn,
                        @ln,
                        @npk,
                        @gender,
                        @bp,
                        @bd,
                        @photo,
                        @addr,
                        @phone,
                        @entry,
                        @pwd,
                        @level,
                        @status,
                        GETDATE()
                    )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", teacherId);
                cmd.Parameters.AddWithValue("@fn", f["first_name"].ToString());
                cmd.Parameters.AddWithValue("@ln", f["last_name"].ToString());
                cmd.Parameters.AddWithValue("@npk", f["npk"].ToString());
                cmd.Parameters.AddWithValue("@gender", f["gender"].ToString());
                cmd.Parameters.AddWithValue("@bp", f["birth_place"].ToString());
                cmd.Parameters.AddWithValue("@bd", f["birth_date"].ToString());
                cmd.Parameters.AddWithValue("@addr", f["address"].ToString());
                cmd.Parameters.AddWithValue("@phone", f["phone"].ToString());
                cmd.Parameters.AddWithValue("@entry", f["entry_date"].ToString());
                cmd.Parameters.AddWithValue("@photo", photoPath ?? "");

                // password = NULL as requested
                cmd.Parameters.AddWithValue("@pwd", DBNull.Value);

                cmd.Parameters.AddWithValue("@level", f["level"].ToString());

                // status always ACTIVE on create
                cmd.Parameters.AddWithValue("@status", "ACTIVE");

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "teacher created"));
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
                var file = Request.Form.Files["profile_photo"];
                var teacherId = f["teacher_id"].ToString();

                using var conn = GetConn();
                conn.Open();

                /* ===== upload photo (optional) ===== */
                string photoSql = "";
                string photoPath = null;

                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/teacher"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = teacherId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    photoPath = "/image/teacher/" + fileName;
                    photoSql = ", profile_photo=@photo";
                }

                var sql = $@"
                    UPDATE mst_teachers SET
                        first_name=@fn,
                        last_name=@ln,
                        npk=@npk,
                        gender=@gender,
                        birth_place=@bp,
                        birth_date=@bd,
                        address=@addr,
                        phone=@phone,
                        entry_date=@entry,
                        level=@level,
                        updated_at=GETDATE()
                        {photoSql}
                    WHERE teacher_id=@id";

                using var cmd = new SqlCommand(sql, conn);

                cmd.Parameters.AddWithValue("@id", teacherId);
                cmd.Parameters.AddWithValue("@fn", f["first_name"].ToString());
                cmd.Parameters.AddWithValue("@ln", f["last_name"].ToString());
                cmd.Parameters.AddWithValue("@npk", f["npk"].ToString());
                cmd.Parameters.AddWithValue("@gender", f["gender"].ToString());
                cmd.Parameters.AddWithValue("@bp", f["birth_place"].ToString());
                cmd.Parameters.AddWithValue("@addr", f["address"].ToString());
                cmd.Parameters.AddWithValue("@phone", f["phone"].ToString());
                cmd.Parameters.AddWithValue("@level", f["level"].ToString());

                cmd.Parameters.AddWithValue(
                    "@bd",
                    string.IsNullOrEmpty(f["birth_date"])
                        ? (object)DBNull.Value
                        : DateTime.Parse(f["birth_date"])
                );

                cmd.Parameters.AddWithValue(
                    "@entry",
                    string.IsNullOrEmpty(f["entry_date"])
                        ? (object)DBNull.Value
                        : DateTime.Parse(f["entry_date"])
                );

                if (photoPath != null)
                    cmd.Parameters.AddWithValue("@photo", photoPath);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "teacher updated"));
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
                    return Json(DTOResponse.fail("invalid teacher id", 400));

                using var conn = GetConn();
                conn.Open();

                // Soft delete: set status = INACTIVE instead of deleting row
                var cmd = new SqlCommand(
                    "UPDATE mst_teachers SET status='INACTIVE', updated_at=GETDATE() WHERE teacher_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "teacher inactivated"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
