using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;
using Haniya.Models;
using Newtonsoft.Json.Linq;

namespace Haniya.Controllers.PortalAdmin
{
    public class StudentController : Controller
    {
        private readonly IConfiguration _config;

        public StudentController(IConfiguration config)
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
            return View("~/Views/PortalAdmin/Student/Index.cshtml");
        }

        public IActionResult Create()
        {
            return View("~/Views/PortalAdmin/Student/Create.cshtml");
        }

        public IActionResult Edit(string id)
        {
            ViewBag.studentId = id;
            return View("~/Views/PortalAdmin/Student/Edit.cshtml");
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

            var orderColumn = "full_name";
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
                    "profile_photo",
                    "nis",
                    "full_name",
                    "birth_date",
                    "birth_place",
                    "gender",
                    "address",
                    "entry_date"
                };

                var (draw, start, length, searchValue, orderColumn, orderDir) = ParseDataTablesQuery(columns);

                using var conn = GetConn();
                conn.Open();

                var totalCmd = new SqlCommand(
                    "SELECT COUNT(*) FROM mst_students WHERE status = 'ACTIVE'",
                    conn
                );
                var recordsTotal = (int)totalCmd.ExecuteScalar();

                string whereSearch = "";
                if (!string.IsNullOrWhiteSpace(searchValue))
                {
                    whereSearch = @" AND (
                        nis LIKE @search OR
                        full_name LIKE @search OR
                        birth_place LIKE @search OR
                        (gender = 'M' AND 'Male' LIKE @search) OR
                        (gender = 'F' AND 'Female' LIKE @search) OR
                        address LIKE @search
                    )";
                }

                var filteredCmd = new SqlCommand(
                    "SELECT COUNT(*) FROM mst_students WHERE status = 'ACTIVE'" + whereSearch,
                    conn
                );

                if (!string.IsNullOrWhiteSpace(searchValue))
                    filteredCmd.Parameters.AddWithValue("@search", $"%{searchValue}%");

                var recordsFiltered = (int)filteredCmd.ExecuteScalar();

                var sql = $@"
                    SELECT
                        student_id,
                        nis,
                        full_name,
                        birth_date,
                        birth_place,
                        CASE 
                            WHEN gender = 'M' THEN 'Male'
                            WHEN gender = 'F' THEN 'Female'
                            ELSE gender
                        END as gender,
                        address,
                        entry_date,
                        profile_photo
                    FROM mst_students
                    WHERE status = 'ACTIVE'
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
                        student_id = rd["student_id"],
                        nis = rd["nis"],
                        full_name = rd["full_name"],
                        birth_date = rd["birth_date"],
                        birth_place = rd["birth_place"],
                        gender = rd["gender"],
                        address = rd["address"],
                        entry_date = rd["entry_date"],
                        profile_photo = rd["profile_photo"]
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

                var sql = @"
                    SELECT s.*, g.item_desc AS gender_name
                    FROM mst_students s
                    LEFT JOIN mst_detail_settings g
                        ON g.detail_id = s.gender
                       AND g.header_id = 'GENDER'
                       AND g.status = 'ACTIVE'
                    WHERE s.student_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                return Json(DTOResponse.ok(new
                {
                    student_id = rd["student_id"]?.ToString(),
                    first_name = rd["first_name"]?.ToString(),
                    last_name = rd["last_name"]?.ToString(),
                    full_name = rd["full_name"]?.ToString(),
                    nis = rd["nis"]?.ToString(),

                    birth_date = rd["birth_date"] == DBNull.Value ? null : ((DateTime)rd["birth_date"]).ToString("yyyy-MM-dd"),
                    birth_place = rd["birth_place"]?.ToString(),
                    gender = rd["gender"]?.ToString(),
                    gender_name = rd["gender_name"]?.ToString(),
                    address = rd["address"]?.ToString(),
                    entry_date = rd["entry_date"] == DBNull.Value ? null : ((DateTime)rd["entry_date"]).ToString("yyyy-MM-dd"),

                    father_name = rd["father_name"]?.ToString(),
                    father_phone = rd["father_phone"]?.ToString(),
                    father_job = rd["father_job"]?.ToString(),
                    mother_name = rd["mother_name"]?.ToString(),
                    mother_phone = rd["mother_phone"]?.ToString(),
                    mother_job = rd["mother_job"]?.ToString(),

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

                var lastCmd = new SqlCommand(
                    "SELECT ISNULL(MAX(student_id),'STD0000') FROM mst_students",
                    conn
                );
                var lastId = lastCmd.ExecuteScalar().ToString();
                var next = int.Parse(lastId.Substring(3)) + 1;
                var studentId = "STD" + next.ToString("D4");

                string photoPath = null;
                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/student"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = studentId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    photoPath = "/image/student/" + fileName;
                }

                var sql = @"
        INSERT INTO mst_students (
            student_id, first_name, last_name, full_name, nis,
            birth_date, birth_place, gender, address,
            father_name, mother_name, father_phone, mother_phone,
            father_job, mother_job,
            entry_date, graduation_date, profile_photo,
            status, created_at
        ) VALUES (
            @id, @fn, @ln, @fullname, @nis,
            @bd, @bp, @gender, @addr,
            @fan, @mon, @fph, @mph,
            @fjob, @mjob,
            @entry, @grad, @photo,
            @status, GETDATE()
        )";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", studentId);
                cmd.Parameters.AddWithValue("@fn", f["first_name"].ToString());
                cmd.Parameters.AddWithValue("@ln", f["last_name"].ToString());
                cmd.Parameters.AddWithValue("@fullname",
                    $"{f["first_name"].ToString()} {f["last_name"].ToString()}");

                cmd.Parameters.AddWithValue("@nis", f["nis"].ToString());
                cmd.Parameters.AddWithValue("@bd", f["birth_date"].ToString());
                cmd.Parameters.AddWithValue("@bp", f["birth_place"].ToString());
                cmd.Parameters.AddWithValue("@gender", f["gender"].ToString());
                cmd.Parameters.AddWithValue("@addr", f["address"].ToString());

                cmd.Parameters.AddWithValue("@fan", f["father_name"].ToString());
                cmd.Parameters.AddWithValue("@mon", f["mother_name"].ToString());
                cmd.Parameters.AddWithValue("@fph", f["father_phone"].ToString());
                cmd.Parameters.AddWithValue("@mph", f["mother_phone"].ToString());
                cmd.Parameters.AddWithValue("@fjob", f["father_job"].ToString());
                cmd.Parameters.AddWithValue("@mjob", f["mother_job"].ToString());

                cmd.Parameters.AddWithValue("@entry", f["entry_date"].ToString());
                cmd.Parameters.AddWithValue("@grad", f["graduation_date"].ToString());
                cmd.Parameters.AddWithValue("@photo", photoPath ?? "");
                cmd.Parameters.AddWithValue("@status", "ACTIVE");

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "student created"));
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
                var studentId = f["student_id"].ToString();

                using var conn = GetConn();
                conn.Open();

                string photoSql = "";
                string photoPath = null;

                if (file != null && file.Length > 0)
                {
                    var folder = Path.Combine(
                        Directory.GetCurrentDirectory(),
                        "wwwroot/image/student"
                    );

                    if (!Directory.Exists(folder))
                        Directory.CreateDirectory(folder);

                    var fileName = studentId + Path.GetExtension(file.FileName);
                    var fullPath = Path.Combine(folder, fileName);

                    using var stream = new FileStream(fullPath, FileMode.Create);
                    file.CopyTo(stream);

                    photoPath = "/image/student/" + fileName;
                    photoSql = ", profile_photo=@photo";
                }

                var sql = $@"
                    UPDATE mst_students SET
                        first_name=@fn,
                        last_name=@ln,
                        full_name=@fullname,
                        nis=@nis,
                        birth_date=@bd,
                        birth_place=@bp,
                        gender=@gender,
                        address=@addr,
                        father_name=@fan,
                        mother_name=@mon,
                        father_phone=@fph,
                        mother_phone=@mph,
                        father_job=@fjob,
                        mother_job=@mjob,
                        entry_date=@entry,
                        graduation_date=@grad,
                        updated_at=GETDATE()
                        {photoSql}
                    WHERE student_id=@id";

                using var cmd = new SqlCommand(sql, conn);

                cmd.Parameters.AddWithValue("@id", studentId);
                cmd.Parameters.AddWithValue("@fn", f["first_name"].ToString());
                cmd.Parameters.AddWithValue("@ln", f["last_name"].ToString());
                cmd.Parameters.AddWithValue(
                    "@fullname",
                    $"{f["first_name"].ToString()} {f["last_name"].ToString()}"
                );
                cmd.Parameters.AddWithValue("@nis", f["nis"].ToString());
                cmd.Parameters.AddWithValue("@bp", f["birth_place"].ToString());
                cmd.Parameters.AddWithValue("@gender", f["gender"].ToString());
                cmd.Parameters.AddWithValue("@addr", f["address"].ToString());
                cmd.Parameters.AddWithValue("@fan", f["father_name"].ToString());
                cmd.Parameters.AddWithValue("@mon", f["mother_name"].ToString());
                cmd.Parameters.AddWithValue("@fph", f["father_phone"].ToString());
                cmd.Parameters.AddWithValue("@mph", f["mother_phone"].ToString());
                cmd.Parameters.AddWithValue("@fjob", f["father_job"].ToString());
                cmd.Parameters.AddWithValue("@mjob", f["mother_job"].ToString());

                cmd.Parameters.AddWithValue(
                    "@bd",
                    string.IsNullOrEmpty(f["birth_date"])
                        ? DBNull.Value
                        : DateTime.Parse(f["birth_date"])
                );

                cmd.Parameters.AddWithValue(
                    "@entry",
                    string.IsNullOrEmpty(f["entry_date"])
                        ? DBNull.Value
                        : DateTime.Parse(f["entry_date"])
                );

                cmd.Parameters.AddWithValue(
                    "@grad",
                    string.IsNullOrEmpty(f["graduation_date"])
                        ? DBNull.Value
                        : DateTime.Parse(f["graduation_date"])
                );

                if (photoPath != null)
                    cmd.Parameters.AddWithValue("@photo", photoPath);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "student updated"));
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
                    return Json(DTOResponse.fail("invalid student id", 400));

                using var conn = GetConn();
                conn.Open();

                var cmd = new SqlCommand(
                    "DELETE FROM mst_students WHERE student_id=@id",
                    conn
                );
                cmd.Parameters.AddWithValue("@id", req.id);

                cmd.ExecuteNonQuery();

                return Json(DTOResponse.ok(null, "student deleted"));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}