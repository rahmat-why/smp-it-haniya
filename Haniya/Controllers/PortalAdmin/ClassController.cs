using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Linq;
using System.Text.RegularExpressions;
using Haniya.Models;

namespace Haniya.Controllers.PortalAdmin
{
    public class ClassController : Controller
    {
        private readonly IConfiguration _config;

        public ClassController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        private static int ParseTrailingNumber(string rawId)
        {
            if (string.IsNullOrWhiteSpace(rawId)) return 0;
            var match = Regex.Match(rawId, @"(\d+)$");
            return match.Success && int.TryParse(match.Value, out var n) ? n : 0;
        }

        [HttpGet]
        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/Class/Index.cshtml");
        }

        [HttpGet]
        public IActionResult Create()
        {
            return View("~/Views/PortalAdmin/Class/Create.cshtml");
        }

        [HttpGet]
        public IActionResult Edit(string id)
        {
            ViewBag.academicClassId = id;
            return View("~/Views/PortalAdmin/Class/Edit.cshtml");
        }

        [HttpGet]
        public IActionResult GetAll(string academic_year_id = null)
        {
            try
            {
                // Map DataTables column index to real SQL field for ORDER BY
                var columnMapping = new Dictionary<int, string>
        {
            { 0, "ay.start_date" },           // Academic Year (sort by start_date)
            { 1, "ay.semester" },             // Semester
            { 2, "c.class_name" },            // Class
            { 3, "t.first_name + ' ' + t.last_name" }, // Homeroom Teacher (name)
            { 4, "student_count" }            // Students
        };

                var (draw, start, length, searchValue, orderColumnIndex, orderDir) = ParseDataTablesQuery();

                string orderColumn = "ay.start_date"; 
                
                if (columnMapping.TryGetValue(orderColumnIndex, out var mappedColumn))
                {
                    orderColumn = mappedColumn;
                }

                using var conn = GetConn();
                conn.Open();

                // Total records
                var totalCmd = new SqlCommand("SELECT COUNT(*) FROM mst_academic_classes", conn);
                var recordsTotal = (int)totalCmd.ExecuteScalar();

                // Build WHERE clause
                string whereSearch = "";
                if (!string.IsNullOrWhiteSpace(searchValue))
                {
                    whereSearch += @" WHERE (
                c.class_name LIKE @search OR
                t.first_name + ' ' + t.last_name LIKE @search OR
                t.npk LIKE @search
            )";
                }
                if (!string.IsNullOrWhiteSpace(academic_year_id))
                {
                    whereSearch += (string.IsNullOrWhiteSpace(whereSearch) ? " WHERE " : " AND ");
                    whereSearch += "ac.academic_year_id = @academic_year_id";
                }

                // Filtered count
                var filteredSql = "SELECT COUNT(*) FROM mst_academic_classes ac " +
                                  "JOIN mst_classes c ON ac.class_id = c.class_id " +
                                  "LEFT JOIN mst_teachers t ON ac.homeroom_teacher_id = t.teacher_id " +
                                  whereSearch;

                using var filteredCmd = new SqlCommand(filteredSql, conn);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    filteredCmd.Parameters.AddWithValue("@search", $"%{searchValue}%");
                if (!string.IsNullOrWhiteSpace(academic_year_id))
                    filteredCmd.Parameters.AddWithValue("@academic_year_id", academic_year_id);

                var recordsFiltered = (int)filteredCmd.ExecuteScalar();

                // Main query
                var sql = $@"
            SELECT
                ac.academic_class_id,
                ac.academic_year_id,
                ay.start_date,
                ay.end_date,
                ay.semester,
                ay.status AS year_status,
                ac.class_id,
                c.class_name,
                ac.homeroom_teacher_id,
                t.first_name,
                t.last_name,
                t.npk,
                t.profile_photo,
                ISNULL(COUNT(sc.student_class_id), 0) AS student_count
            FROM mst_academic_classes ac
            JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
            JOIN mst_classes c ON ac.class_id = c.class_id
            LEFT JOIN mst_teachers t ON ac.homeroom_teacher_id = t.teacher_id
            LEFT JOIN mst_student_classes sc ON sc.academic_class_id = ac.academic_class_id
            {whereSearch}
            GROUP BY
                ac.academic_class_id,
                ac.academic_year_id,
                ay.start_date,
                ay.end_date,
                ay.semester,
                ay.status,
                ac.class_id,
                c.class_name,
                ac.homeroom_teacher_id,
                t.first_name,
                t.last_name,
                t.npk,
                t.profile_photo
            ORDER BY {orderColumn} {orderDir}
            OFFSET @start ROWS FETCH NEXT @length ROWS ONLY";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@start", start);
                cmd.Parameters.AddWithValue("@length", length);
                if (!string.IsNullOrWhiteSpace(searchValue))
                    cmd.Parameters.AddWithValue("@search", $"%{searchValue}%");
                if (!string.IsNullOrWhiteSpace(academic_year_id))
                    cmd.Parameters.AddWithValue("@academic_year_id", academic_year_id);

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    var tFirst = rd["first_name"]?.ToString() ?? "";
                    var tLast = rd["last_name"]?.ToString() ?? "";
                    var tFull = string.Join(" ", new[] { tFirst, tLast }.Where(s => !string.IsNullOrWhiteSpace(s)));

                    list.Add(new
                    {
                        academic_class_id = rd["academic_class_id"],
                        academic_year_id = rd["academic_year_id"],
                        start_date = rd["start_date"],
                        end_date = rd["end_date"],
                        semester = rd["semester"],
                        year_status = rd["year_status"],
                        class_name = rd["class_name"],
                        homeroom_teacher_id = rd["homeroom_teacher_id"],
                        homeroom_teacher_name = tFull,
                        homeroom_teacher_npk = rd["npk"],
                        homeroom_teacher_photo = rd["profile_photo"],
                        student_count = rd["student_count"]
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

        // Fixed ParseDataTablesQuery - no default "title"
        private (int draw, int start, int length, string searchValue, int orderColumnIndex, string orderDir)
        ParseDataTablesQuery()
        {
            var q = Request.Query;
            int.TryParse(q["draw"], out var draw);
            if (draw <= 0) draw = 1;
            int.TryParse(q["start"], out var start);
            if (start < 0) start = 0;
            int.TryParse(q["length"], out var length);
            if (length <= 0) length = 10;
            var searchValue = q["search[value]"].ToString() ?? string.Empty;

            int orderColumnIndex = 0; // default to column 0 (Academic Year)
            var orderColIdxStr = q["order[0][column]"].ToString();
            if (int.TryParse(orderColIdxStr, out var idx))
            {
                orderColumnIndex = idx;
            }

            var dir = q["order[0][dir]"].ToString();
            var orderDir = "ASC";
            if (!string.IsNullOrWhiteSpace(dir) &&
                (dir.Equals("asc", StringComparison.OrdinalIgnoreCase) ||
                 dir.Equals("desc", StringComparison.OrdinalIgnoreCase)))
            {
                orderDir = dir.ToUpper();
            }

            return (draw, start, length, searchValue, orderColumnIndex, orderDir);
        }

        [HttpGet]
        public IActionResult GetById(string id)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(id))
                    return Json(DTOResponse.fail("invalid academic class id", 400));

                using var conn = GetConn();
                conn.Open();

                var sql = @"
                    SELECT
                        ac.academic_class_id,
                        ac.academic_year_id,
                        ac.class_id,
                        ac.homeroom_teacher_id,
                        ay.start_date,
                        ay.end_date,
                        ay.semester,
                        c.class_name,
                        c.class_level,
                        t.first_name AS teacher_first_name,
                        t.last_name AS teacher_last_name,
                        t.npk AS teacher_npk
                    FROM mst_academic_classes ac
                    JOIN mst_academic_years ay ON ac.academic_year_id = ay.academic_year_id
                    JOIN mst_classes c ON ac.class_id = c.class_id
                    LEFT JOIN mst_teachers t ON ac.homeroom_teacher_id = t.teacher_id
                    WHERE ac.academic_class_id = @id";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@id", id);

                using var rd = cmd.ExecuteReader();
                if (!rd.Read())
                    return Json(DTOResponse.fail("data not found", 404));

                var teacherFirstName = rd["teacher_first_name"]?.ToString() ?? "";
                var teacherLastName = rd["teacher_last_name"]?.ToString() ?? "";
                var teacherFullName = string.Join(" ", new[] { teacherFirstName, teacherLastName }.Where(x => !string.IsNullOrWhiteSpace(x)));
                var teacherNpk = rd["teacher_npk"]?.ToString() ?? "";

                return Json(DTOResponse.ok(new
                {
                    academic_class_id = rd["academic_class_id"]?.ToString(),
                    academic_year_id = rd["academic_year_id"]?.ToString(),
                    class_id = rd["class_id"]?.ToString(),
                    homeroom_teacher_id = rd["homeroom_teacher_id"]?.ToString(),
                    start_date = rd["start_date"] == DBNull.Value ? null : ((DateTime)rd["start_date"]).ToString("yyyy-MM-dd"),
                    end_date = rd["end_date"] == DBNull.Value ? null : ((DateTime)rd["end_date"]).ToString("yyyy-MM-dd"),
                    semester = rd["semester"]?.ToString(),
                    class_name = rd["class_name"]?.ToString(),
                    class_level = rd["class_level"]?.ToString(),
                    homeroom_teacher_name = teacherFullName,
                    homeroom_teacher_npk = teacherNpk
                }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetUnassignedStudents()
        {
            try
            {
                var students = new List<object>();

                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT 
                s.student_id,
                s.full_name,
                s.nis,
                s.gender,
                s.status,
                s.profile_photo,
                mds.item_name as gender_display
            FROM mst_students s
            JOIN mst_detail_settings mds ON s.gender = mds.detail_id
            LEFT JOIN mst_student_classes sc ON sc.student_id = s.student_id
            WHERE s.status = 'ACTIVE'
              AND sc.student_id IS NULL  -- only unassigned students
            ORDER BY s.full_name";

                using var cmd = new SqlCommand(sql, conn);
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    students.Add(new
                    {
                        student_id = rd["student_id"].ToString(),
                        full_name = rd["full_name"]?.ToString(),
                        nis = rd["nis"]?.ToString(),
                        gender = rd["gender_display"]?.ToString() ?? "Unknown",
                        status = rd["status"]?.ToString(),
                        profile_photo = rd["profile_photo"]?.ToString() ?? "/image/no-image.png"
                    });
                }

                return Json(DTOResponse.ok(students));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        [HttpGet]
        public IActionResult GetStudentsWithAssignmentStatus(string academicClassId)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("Invalid academic class ID", 400));

                var students = new List<object>();

                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT 
                s.student_id,
                s.full_name,
                s.nis,
                s.gender,
                s.profile_photo,
                mds.item_name as gender_display,
                CASE 
                    WHEN EXISTS (
                        SELECT 1
                        FROM mst_student_classes sc_in_class
                        WHERE sc_in_class.student_id = s.student_id
                          AND sc_in_class.academic_class_id = @academicClassId
                    ) THEN 1
                    ELSE 0 
                END AS is_assigned
            FROM mst_students s
            JOIN mst_detail_settings mds ON s.gender = mds.detail_id
            WHERE s.status = 'ACTIVE'
              AND (
                    EXISTS (
                        SELECT 1
                        FROM mst_student_classes sc_current
                        WHERE sc_current.student_id = s.student_id
                          AND sc_current.academic_class_id = @academicClassId
                    )
                    OR NOT EXISTS (
                        SELECT 1
                        FROM mst_student_classes sc_other
                        WHERE sc_other.student_id = s.student_id
                          AND sc_other.academic_class_id <> @academicClassId
                    )
              )
            ORDER BY s.full_name";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@academicClassId", academicClassId);

                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    students.Add(new
                    {
                        student_id = rd["student_id"].ToString(),
                        full_name = rd["full_name"]?.ToString(),
                        nis = rd["nis"]?.ToString(),
                        gender = rd["gender_display"]?.ToString() ?? "Unknown",
                        profile_photo = rd["profile_photo"]?.ToString() ?? "/image/no-image.png",
                        is_assigned = Convert.ToBoolean(rd["is_assigned"])
                    });
                }

                return Json(DTOResponse.ok(students));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        /// <summary>
        /// CREATE ONE academic class + student list (student_classes)
        /// </summary>
        [HttpPost]
        public IActionResult Create(DTORequest req)
        {
            try
            {
                var f = Request.Form;

                var academicYearId = f["academic_year_id"].ToString();
                var classId = f["class_id"].ToString();
                var homeroomTeacherId = f["homeroom_teacher_id"].ToString();
                var studentIds = f["student_ids"]; // multiple

                if (string.IsNullOrWhiteSpace(academicYearId))
                    return Json(DTOResponse.fail("academic year is required", 400));

                if (string.IsNullOrWhiteSpace(classId))
                    return Json(DTOResponse.fail("class is required", 400));

                if (string.IsNullOrWhiteSpace(homeroomTeacherId))
                    return Json(DTOResponse.fail("homeroom teacher is required", 400));

                if (studentIds.Count == 0)
                    return Json(DTOResponse.fail("at least one student must be selected", 400));

                using var conn = GetConn();
                conn.Open();

                using var tran = conn.BeginTransaction();

                try
                {
                    // Check duplicate academic class for same year + class
                    var existsCmd = new SqlCommand(@"
                        SELECT COUNT(1) 
                        FROM mst_academic_classes
                        WHERE academic_year_id = @year AND class_id = @class",
                        conn, tran);
                    existsCmd.Parameters.AddWithValue("@year", academicYearId);
                    existsCmd.Parameters.AddWithValue("@class", classId);

                    var exists = (int)existsCmd.ExecuteScalar();
                    if (exists > 0)
                    {
                        tran.Rollback();
                        return Json(DTOResponse.fail("academic class already exists for this year and class", 400));
                    }

                    // Generate new academic_class_id
                    var lastAccCmd = new SqlCommand(
                        "SELECT ISNULL(MAX(academic_class_id),'ACC0000') FROM mst_academic_classes",
                        conn, tran);
                    var lastAccId = lastAccCmd.ExecuteScalar()?.ToString() ?? "ACC0000";
                    var accCurrent = ParseTrailingNumber(lastAccId);
                    accCurrent++;
                    var academicClassId = "ACC" + accCurrent.ToString("D4");

                    var insertAccSql = @"
                        INSERT INTO mst_academic_classes (
                            academic_class_id,
                            academic_year_id,
                            class_id,
                            homeroom_teacher_id,
                            created_at
                        ) VALUES (
                            @id,
                            @year,
                            @class,
                            @teacher,
                            GETDATE()
                        )";

                    using (var cmdAcc = new SqlCommand(insertAccSql, conn, tran))
                    {
                        cmdAcc.Parameters.AddWithValue("@id", academicClassId);
                        cmdAcc.Parameters.AddWithValue("@year", academicYearId);
                        cmdAcc.Parameters.AddWithValue("@class", classId);
                        cmdAcc.Parameters.AddWithValue("@teacher", homeroomTeacherId);
                        cmdAcc.ExecuteNonQuery();
                    }

                    // Prepare student_class_id sequence
                    var lastStcCmd = new SqlCommand(
                        "SELECT ISNULL(MAX(student_class_id),'STC0000') FROM mst_student_classes",
                        conn, tran);
                    var lastStcId = lastStcCmd.ExecuteScalar()?.ToString() ?? "STC0000";
                    var stcCurrent = ParseTrailingNumber(lastStcId);

                    foreach (var studentId in studentIds)
                    {
                        if (string.IsNullOrWhiteSpace(studentId))
                            continue;

                        stcCurrent++;
                        var studentClassId = "STC" + stcCurrent.ToString("D4");

                        var sqlSt = @"
                            INSERT INTO mst_student_classes (
                                student_class_id,
                                student_id,
                                academic_class_id,
                                created_at
                            ) VALUES (
                                @id,
                                @student,
                                @ac,
                                GETDATE()
                            )";

                        using var cmdSt = new SqlCommand(sqlSt, conn, tran);
                        cmdSt.Parameters.AddWithValue("@id", studentClassId);
                        cmdSt.Parameters.AddWithValue("@student", studentId);
                        cmdSt.Parameters.AddWithValue("@ac", academicClassId);
                        cmdSt.ExecuteNonQuery();
                    }

                    tran.Commit();

                    return Json(DTOResponse.ok(null, "academic class and students created"));
                }
                catch (Exception exIn)
                {
                    tran.Rollback();
                    return Json(DTOResponse.fail(exIn.Message, 500));
                }
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        /// <summary>
        /// UPDATE academic class + full student list (replace assignments)
        /// </summary>
        [HttpPost]
        public IActionResult Update(DTORequest req)
        {
            try
            {
                var f = Request.Form;
                var academicClassId = f["academic_class_id"].ToString();

                if (string.IsNullOrWhiteSpace(academicClassId))
                    return Json(DTOResponse.fail("invalid academic class id", 400));

                var academicYearId = f["academic_year_id"].ToString();
                var classId = f["class_id"].ToString();
                var homeroomTeacherId = f["homeroom_teacher_id"].ToString();
                var studentIds = f["student_ids"]; // multiple

                if (string.IsNullOrWhiteSpace(academicYearId))
                    return Json(DTOResponse.fail("academic year is required", 400));
                if (string.IsNullOrWhiteSpace(classId))
                    return Json(DTOResponse.fail("class is required", 400));
                if (string.IsNullOrWhiteSpace(homeroomTeacherId))
                    return Json(DTOResponse.fail("homeroom teacher is required", 400));
                if (studentIds.Count == 0)
                    return Json(DTOResponse.fail("at least one student must be selected", 400));

                using var conn = GetConn();
                conn.Open();

                using var tran = conn.BeginTransaction();

                try
                {
                    // Update academic class
                    var sqlAcc = @"
                        UPDATE mst_academic_classes SET
                            academic_year_id = @year,
                            class_id = @class,
                            homeroom_teacher_id = @teacher,
                            updated_at = GETDATE()
                        WHERE academic_class_id = @id";

                    using (var cmdAcc = new SqlCommand(sqlAcc, conn, tran))
                    {
                        cmdAcc.Parameters.AddWithValue("@id", academicClassId);
                        cmdAcc.Parameters.AddWithValue("@year", academicYearId);
                        cmdAcc.Parameters.AddWithValue("@class", classId);
                        cmdAcc.Parameters.AddWithValue("@teacher", homeroomTeacherId);
                        cmdAcc.ExecuteNonQuery();
                    }

                    // Current assigned students
                    var currentStudents = new HashSet<string>();
                    var curCmd = new SqlCommand(@"
                        SELECT student_id 
                        FROM mst_student_classes 
                        WHERE academic_class_id = @ac",
                        conn, tran);
                    curCmd.Parameters.AddWithValue("@ac", academicClassId);
                    using (var rd = curCmd.ExecuteReader())
                    {
                        while (rd.Read())
                        {
                            currentStudents.Add(rd["student_id"].ToString());
                        }
                    }

                    var selectedStudents = new HashSet<string>(studentIds.Where(s => !string.IsNullOrWhiteSpace(s)));

                    // Prevent assigning students that already belong to another class
                    foreach (var sid in selectedStudents)
                    {
                        var conflictCmd = new SqlCommand(@"
                            SELECT TOP 1 academic_class_id
                            FROM mst_student_classes
                            WHERE student_id = @sid
                              AND academic_class_id <> @ac",
                            conn, tran);
                        conflictCmd.Parameters.AddWithValue("@sid", sid);
                        conflictCmd.Parameters.AddWithValue("@ac", academicClassId);

                        var conflictClassId = conflictCmd.ExecuteScalar()?.ToString();
                        if (!string.IsNullOrWhiteSpace(conflictClassId))
                        {
                            tran.Rollback();
                            return Json(DTOResponse.fail($"student {sid} already assigned to another class ({conflictClassId})", 400));
                        }
                    }

                    var toAdd = selectedStudents.Except(currentStudents).ToList();
                    var toRemove = currentStudents.Except(selectedStudents).ToList();

                    // Remove unselected students
                    if (toRemove.Any())
                    {
                        var delCmd = new SqlCommand(@"
                            DELETE FROM mst_student_classes
                            WHERE academic_class_id = @ac
                              AND student_id = @st",
                            conn, tran);

                        delCmd.Parameters.Add("@ac", System.Data.SqlDbType.VarChar);
                        delCmd.Parameters.Add("@st", System.Data.SqlDbType.VarChar);

                        foreach (var sid in toRemove)
                        {
                            delCmd.Parameters["@ac"].Value = academicClassId;
                            delCmd.Parameters["@st"].Value = sid;
                            delCmd.ExecuteNonQuery();
                        }
                    }

                    // Prepare new ID sequence for additions
                    if (toAdd.Any())
                    {
                        var lastStcCmd = new SqlCommand(
                            "SELECT ISNULL(MAX(student_class_id),'STC0000') FROM mst_student_classes",
                            conn, tran);
                        var lastStcId = lastStcCmd.ExecuteScalar()?.ToString() ?? "STC0000";
                        var stcCurrent = ParseTrailingNumber(lastStcId);

                        foreach (var sid in toAdd)
                        {
                            stcCurrent++;
                            var studentClassId = "STC" + stcCurrent.ToString("D4");

                            var insSql = @"
                                INSERT INTO mst_student_classes (
                                    student_class_id,
                                    student_id,
                                    academic_class_id,
                                    created_at
                                ) VALUES (
                                    @id,
                                    @student,
                                    @ac,
                                    GETDATE()
                                )";

                            using var insCmd = new SqlCommand(insSql, conn, tran);
                            insCmd.Parameters.AddWithValue("@id", studentClassId);
                            insCmd.Parameters.AddWithValue("@student", sid);
                            insCmd.Parameters.AddWithValue("@ac", academicClassId);
                            insCmd.ExecuteNonQuery();
                        }
                    }

                    tran.Commit();

                    return Json(DTOResponse.ok(null, "class and students updated"));
                }
                catch (Exception exIn)
                {
                    tran.Rollback();
                    return Json(DTOResponse.fail(exIn.Message, 500));
                }
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        /// <summary>
        /// Delete academic class + all its student_classes
        /// </summary>
        [HttpPost]
        public IActionResult Delete([FromBody] DTORequest req)
        {
            try
            {
                if (string.IsNullOrEmpty(req?.id))
                    return Json(DTOResponse.fail("invalid academic class id", 400));

                using var conn = GetConn();
                conn.Open();

                using var tran = conn.BeginTransaction();

                try
                {
                    // Delete student_classes first
                    var delStCmd = new SqlCommand(
                        "DELETE FROM mst_student_classes WHERE academic_class_id=@id",
                        conn, tran
                    );
                    delStCmd.Parameters.AddWithValue("@id", req.id);
                    delStCmd.ExecuteNonQuery();

                    // Delete academic_class
                    var delAccCmd = new SqlCommand(
                        "DELETE FROM mst_academic_classes WHERE academic_class_id=@id",
                        conn, tran
                    );
                    delAccCmd.Parameters.AddWithValue("@id", req.id);
                    delAccCmd.ExecuteNonQuery();

                    tran.Commit();

                    return Json(DTOResponse.ok(null, "academic class and students deleted"));
                }
                catch (Exception exIn)
                {
                    tran.Rollback();
                    return Json(DTOResponse.fail(exIn.Message, 500));
                }
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
