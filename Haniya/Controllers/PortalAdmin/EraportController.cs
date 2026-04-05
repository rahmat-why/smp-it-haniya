using Haniya.Models;
using Microsoft.AspNetCore.Mvc;
using Rotativa.AspNetCore;
using System.Data;
using System.Data.SqlClient;


namespace Haniya.Controllers.PortalAdmin
{
    public class EraportController : Controller
    {
        private readonly IConfiguration _config;

        public EraportController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
        {
            return new SqlConnection(_config.GetConnectionString("DefaultConnection"));
        }

        private static int SafeToInt(object? value)
        {
            if (value == null || value == DBNull.Value) return 0;
            return Convert.ToInt32(value);
        }

        public IActionResult Index()
        {
            return View("~/Views/PortalAdmin/E-Raport/Index.cshtml");
        }

        public class ListSort
        {
            public string field { get; set; } = "student";
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
                var offset = (page - 1) * limit;
                var take = limit + 1;

                var filters = req.filters ?? new Dictionary<string, string>();
                filters.TryGetValue("search", out var search);
                filters.TryGetValue("academicYearId", out var academicYearId);
                filters.TryGetValue("classLevel", out var classLevel);

                var sortMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
                {
                    ["student"] = "s.full_name",
                    ["academicYears"] = "ay.start_date",
                    ["semester"] = "ay.semester",
                    ["class"] = "c.class_name",
                    ["summaryAttendance"] = "attendance_summary.present",
                    ["summaryGrade"] = "grade_summary.passed"
                };
                var sort = req.sort ?? new ListSort();
                var orderBy = sortMap.TryGetValue(sort.field ?? "", out var mapped) ? mapped : "s.full_name";
                var orderDir = string.Equals(sort.order, "desc", StringComparison.OrdinalIgnoreCase) ? "DESC" : "ASC";

                using var conn = GetConn();
                conn.Open();

                string baseQuery = @"
                    FROM mst_students s
                    LEFT JOIN mst_student_classes sc 
                        ON sc.student_id = s.student_id
                    LEFT JOIN mst_academic_classes ac 
                        ON ac.academic_class_id = sc.academic_class_id
                    LEFT JOIN mst_classes c 
                        ON c.class_id = ac.class_id
                    LEFT JOIN mst_academic_years ay
                        ON ay.academic_year_id = ac.academic_year_id

                    -- GRADE SUMMARY
                    LEFT JOIN (
                        SELECT 
                            d.student_id,
                            COUNT(d.grade_detail_id) AS total_graded,
                            SUM(CASE 
                                WHEN TRY_CAST(REPLACE(d.grade_value, ',', '.') AS FLOAT) 
                                     >= ISNULL(r.minimum_value,0)
                                THEN 1 ELSE 0 END) AS passed,
                            SUM(CASE 
                                WHEN TRY_CAST(REPLACE(d.grade_value, ',', '.') AS FLOAT) 
                                     < ISNULL(r.minimum_value,0)
                                     OR TRY_CAST(REPLACE(d.grade_value, ',', '.') AS FLOAT) IS NULL
                                THEN 1 ELSE 0 END) AS remedial
                        FROM txn_grade_details d
                        JOIN txn_grades g ON g.grade_id = d.grade_id
                        LEFT JOIN mst_rps r 
                            ON r.academic_class_id = g.academic_class_id
                           AND r.subject_id = g.subject_id
                        GROUP BY d.student_id
                    ) grade_summary 
                        ON grade_summary.student_id = s.student_id

                    -- ATTENDANCE SUMMARY
                    LEFT JOIN (
                        SELECT 
                            d.student_id,
                            SUM(CASE WHEN d.status='PRESENT' THEN 1 ELSE 0 END) AS present,
                            SUM(CASE WHEN d.status='SICK' THEN 1 ELSE 0 END) AS sick,
                            SUM(CASE WHEN d.status='EXCUSED' THEN 1 ELSE 0 END) AS permit,
                            SUM(CASE WHEN d.status='NOINFO' THEN 1 ELSE 0 END) AS alpha
                        FROM txn_attendance_details d
                        JOIN txn_attendances a 
                            ON a.attendance_id = d.attendance_id
                        GROUP BY d.student_id
                    ) attendance_summary
                        ON attendance_summary.student_id = s.student_id

                    WHERE s.status = 'ACTIVE'
                ";

                var conditions = new List<string>();
                if (!string.IsNullOrWhiteSpace(search))
                    conditions.Add(@"(s.nis LIKE @search OR s.full_name LIKE @search OR c.class_name LIKE @search)");

                if (!string.IsNullOrEmpty(academicYearId))
                    conditions.Add("ac.academic_year_id = @academicYearId");

                if (!string.IsNullOrEmpty(classLevel))
                    conditions.Add("c.class_name LIKE @classLevel");

                string whereSearch = conditions.Count > 0
                    ? " AND " + string.Join(" AND ", conditions)
                    : "";

                var sql = $@"
                    SELECT 
                        s.student_id,
                        ac.academic_class_id,
                        s.nis,
                        s.full_name,
                        s.profile_photo,
                        ay.start_date,
                        ay.end_date,
                        ay.semester,
                        c.class_name,

                        ISNULL(grade_summary.total_graded,0) AS total_graded,
                        ISNULL(grade_summary.passed,0) AS passed,
                        ISNULL(grade_summary.remedial,0) AS remedial,

                        ISNULL(attendance_summary.present,0) AS present,
                        ISNULL(attendance_summary.sick,0) AS sick,
                        ISNULL(attendance_summary.permit,0) AS permit,
                        ISNULL(attendance_summary.alpha,0) AS alpha

                    {baseQuery}
                    {whereSearch}

                    ORDER BY {orderBy} {orderDir}
                    OFFSET @offset ROWS FETCH NEXT @take ROWS ONLY
                ";

                using var cmd = new SqlCommand(sql, conn);
                cmd.Parameters.AddWithValue("@offset", offset);
                cmd.Parameters.AddWithValue("@take", take);

                if (!string.IsNullOrWhiteSpace(search))
                    cmd.Parameters.Add("@search", SqlDbType.NVarChar, 100)
                        .Value = $"%{search.Trim()}%";

                if (!string.IsNullOrEmpty(academicYearId))
                    cmd.Parameters.Add("@academicYearId", SqlDbType.NVarChar)
                        .Value = academicYearId;

                if (!string.IsNullOrEmpty(classLevel))
                    cmd.Parameters.Add("@classLevel", SqlDbType.NVarChar)
                        .Value = $"%{classLevel}%";

                var list = new List<object>();
                using var rd = cmd.ExecuteReader();
                while (rd.Read())
                {
                    list.Add(new
                    {
                        student_id = rd["student_id"],
                        academic_class_id = rd["academic_class_id"],
                        nis = rd["nis"],
                        full_name = rd["full_name"],
                        profile_photo = rd["profile_photo"],
                        start_date = rd["start_date"],
                        end_date = rd["end_date"],
                        semester = rd["semester"],
                        class_name = rd["class_name"] ?? "-",

                        total_graded = Convert.ToInt32(rd["total_graded"]),
                        passed = Convert.ToInt32(rd["passed"]),
                        remedial = Convert.ToInt32(rd["remedial"]),

                        present = Convert.ToInt32(rd["present"]),
                        sick = Convert.ToInt32(rd["sick"]),
                        permit = Convert.ToInt32(rd["permit"]),
                        alpha = Convert.ToInt32(rd["alpha"])
                    });
                }

                var hasNextPage = list.Count > limit;
                if (hasNextPage) list = list.Take(limit).ToList();

                return Json(DTOResponse.ok(new { data = list, hasNextPage }));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }

        public DataTable GetStudentReport(string studentId, string classId)
        {
            using var conn = GetConn();
            conn.Open();

            string query = @"
                SELECT 
                s.subject_id,
                s.subject_name,
                s.subject_code,
                s.subject_type,
                a.attendance_score,

                (
                    (ISNULL(a.attendance_score,0) * ISNULL(r.weight_attendance,0) / 100.0) +
                    (ISNULL(AVG(CASE WHEN g.grade_type='GRADE_TASK' THEN gd.grade_value END),0) * ISNULL(r.weight_task,0) / 100.0) +
                    (ISNULL(AVG(CASE WHEN g.grade_type='GRADE_TEST' THEN gd.grade_value END),0) * ISNULL(r.weight_uh,0) / 100.0) +
                    (ISNULL(AVG(CASE WHEN g.grade_type='GRADE_MID' THEN gd.grade_value END),0) * ISNULL(r.weight_pts,0) / 100.0) +
                    (ISNULL(AVG(CASE WHEN g.grade_type='GRADE_FINAL' THEN gd.grade_value END),0) * ISNULL(r.weight_pas,0) / 100.0)
                ) AS final_score

            FROM mst_subjects s

            JOIN mst_academic_classes ac 
               ON ac.academic_class_id = @classId

            JOIN mst_classes c
                ON c.class_id = ac.class_id

            LEFT JOIN txn_grades g
                ON g.subject_id = s.subject_id
                AND g.academic_class_id = @classId

            LEFT JOIN txn_grade_details gd
                ON g.grade_id = gd.grade_id
                AND gd.student_id = @studentId

            OUTER APPLY (
                SELECT TOP 1 *
                FROM mst_rps r
                WHERE r.subject_id = s.subject_id
                AND r.academic_class_id = @classId
                ORDER BY r.created_at DESC
            ) r

            LEFT JOIN
            (
                SELECT 
                    d.student_id,
                    a.academic_class_id,
                    (SUM(CASE WHEN d.status='PRESENT' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) AS attendance_score
                FROM txn_attendance_details d
                JOIN txn_attendances a 
                    ON a.attendance_id = d.attendance_id
                GROUP BY d.student_id, a.academic_class_id
            ) a
            ON a.student_id = @studentId
            AND a.academic_class_id = @classId
            WHERE 
                s.status = 'ACTIVE'
                AND s.class_level = LEFT(c.class_name, 1)

            GROUP BY 
                s.subject_id,
                s.subject_name,
                s.subject_code,
                s.subject_type,
                a.attendance_score,
                r.weight_attendance,
                r.weight_task,
                r.weight_uh,
                r.weight_pts,
                r.weight_pas

            ORDER BY s.subject_name";

            using var cmd = new SqlCommand(query, conn);
            cmd.Parameters.AddWithValue("@studentId", studentId);
            cmd.Parameters.AddWithValue("@classId", classId);

            SqlDataAdapter da = new SqlDataAdapter(cmd);
            DataTable dt = new DataTable();
            da.Fill(dt);

            return dt;
        }

        public IActionResult ExportPdf(string id, string classId)
        {
            Dictionary<string, object> data = new Dictionary<string, object>();

            using (var conn = GetConn())
            {
                conn.Open();

                var cmd = new SqlCommand(@"
                    SELECT 
                        s.student_id,
                        s.nis,
                        s.full_name,
                        s.address AS school_address,
                        s.father_name AS parent_name,
                        c.class_id,
                        c.class_name,
                        ac.academic_class_id,
                        ay.academic_year_id,
                        ay.semester,
                        ay.start_date,
                        ay.end_date,
                        (CAST(YEAR(ay.start_date) AS VARCHAR) + '/' + CAST(YEAR(ay.end_date) AS VARCHAR)) AS academic_year_name,
                        t.full_name AS teacher_name,
                        t.npk AS teacher_npk
                        
                    FROM mst_students s
                    LEFT JOIN mst_student_classes sc 
                        ON sc.student_id = s.student_id
                    LEFT JOIN mst_academic_classes ac 
                        ON ac.academic_class_id = sc.academic_class_id
                    LEFT JOIN mst_classes c 
                        ON c.class_id = ac.class_id
                    LEFT JOIN mst_academic_years ay
                        ON ay.academic_year_id = ac.academic_year_id
                    LEFT JOIN mst_teachers t
                        ON t.teacher_id = ac.homeroom_teacher_id
                    WHERE s.student_id = @id
                    AND ac.academic_class_id = @classId
                ", conn);

                cmd.Parameters.AddWithValue("@id", id);
                cmd.Parameters.AddWithValue("@classId", classId);

                var reader = cmd.ExecuteReader();

                if (reader.Read())
                {
                    data["student_id"] = reader["student_id"];
                    data["nis"] = reader["nis"];
                    data["full_name"] = reader["full_name"];
                    data["class_name"] = reader["class_name"];
                    data["school_address"] = reader["school_address"];
                    data["semester"] = reader["semester"];
                    data["start_date"] = reader["start_date"];
                    data["end_date"] = reader["end_date"];
                    data["teacher_name"] = reader["teacher_name"];

                    data["class_id"] = reader["class_id"];
                    data["academic_year_id"] = reader["academic_year_id"];
                    data["academic_year_name"] = reader["academic_year_name"];
                    data["teacher_npk"] = reader["teacher_npk"];
                    data["parent_name"] = reader["parent_name"];
                    data["academic_class_id"] = reader["academic_class_id"];


                    classId = reader["academic_class_id"].ToString();
                }
                else
                {
                    return Content("DATA STUDENT TIDAK DITEMUKAN");
                }

                reader.Close();

                var attendanceCmd = new SqlCommand(@"
                    SELECT 
                        SUM(CASE WHEN d.status='SICK' THEN 1 ELSE 0 END) AS sick,
                        SUM(CASE WHEN d.status='EXCUSED' THEN 1 ELSE 0 END) AS permit,
                        SUM(CASE WHEN d.status='NOINFO' THEN 1 ELSE 0 END) AS alpha
                    FROM txn_attendance_details d
                    JOIN txn_attendances a 
                        ON a.attendance_id = d.attendance_id
                    WHERE d.student_id = @studentId
                    AND a.academic_class_id = @classId
                ", conn);

                attendanceCmd.Parameters.AddWithValue("@studentId", id);
                attendanceCmd.Parameters.AddWithValue("@classId", classId);

                var attReader = attendanceCmd.ExecuteReader();

                if (attReader.Read())
                {
                    data["sick"] = Convert.ToInt32(attReader["sick"]);
                    data["permit"] = Convert.ToInt32(attReader["permit"]);
                    data["alpha"] = Convert.ToInt32(attReader["alpha"]);
                }

                attReader.Close();

                var headCmd = new SqlCommand(@"
                    SELECT detail_id, item_desc
                    FROM mst_detail_setting_landingpages
                    WHERE detail_id IN ('ABOUT_HEADSC_NAME1', 'HOME_SCHOOL_TITLE1', 'ABOUT_HEADSC_NPK1')
                ", conn);

                var headReader = headCmd.ExecuteReader();

                while (headReader.Read())
                {
                    var key = headReader["detail_id"].ToString();
                    var value = headReader["item_desc"]?.ToString() ?? "";

                    if (key == "ABOUT_HEADSC_NAME1")
                        data["headschool_name"] = value;

                    if (key == "HOME_SCHOOL_TITLE1")
                        data["school_name"] = value;

                    if (key == "ABOUT_HEADSC_NPK1")
                        data["headschool_npk"] = value;


                }

                headReader.Close();

                // ambil nilai raport
                data["report"] = GetStudentReport(id, classId);
            }

            return new ViewAsPdf("~/Views/PortalAdmin/E-Raport/EraportPdf.cshtml", data)
            {
                PageSize = Rotativa.AspNetCore.Options.Size.A4,
                CustomSwitches = "--print-media-type --disable-smart-shrinking"

            };
        }

        [HttpGet]
        public IActionResult Preview(string id, string classId)
        {
            Dictionary<string, object> data = new Dictionary<string, object>();

            using (var conn = GetConn())
            {
                conn.Open();

                var cmd = new SqlCommand(@"
                    SELECT 
                        s.student_id,
                        s.nis,
                        s.full_name,
                        s.address AS school_address,
                        s.father_name AS parent_name,
                        c.class_id,
                        c.class_name,
                        ac.academic_class_id,
                        ay.academic_year_id,
                        ay.semester,
                        ay.start_date,
                        ay.end_date,
                        (CAST(YEAR(ay.start_date) AS VARCHAR) + '/' + CAST(YEAR(ay.end_date) AS VARCHAR)) AS academic_year_name,
                        t.full_name AS teacher_name,
                        t.npk AS teacher_npk
                    FROM mst_students s
                    LEFT JOIN mst_student_classes sc 
                        ON sc.student_id = s.student_id
                    LEFT JOIN mst_academic_classes ac 
                        ON ac.academic_class_id = sc.academic_class_id
                    LEFT JOIN mst_classes c 
                        ON c.class_id = ac.class_id
                    LEFT JOIN mst_academic_years ay
                        ON ay.academic_year_id = ac.academic_year_id
                    LEFT JOIN mst_teachers t
                        ON t.teacher_id = ac.homeroom_teacher_id
                    WHERE s.student_id = @id
                    AND ac.academic_class_id = @classId
                ", conn);

                cmd.Parameters.AddWithValue("@id", id);
                cmd.Parameters.AddWithValue("@classId", classId);

                var reader = cmd.ExecuteReader();

                if (reader.Read())
                {
                    data["student_id"] = reader["student_id"];
                    data["nis"] = reader["nis"];
                    data["full_name"] = reader["full_name"];
                    data["class_name"] = reader["class_name"];
                    data["school_address"] = reader["school_address"];
                    data["semester"] = reader["semester"];
                    data["start_date"] = reader["start_date"];
                    data["end_date"] = reader["end_date"];
                    data["teacher_name"] = reader["teacher_name"];

                    data["class_id"] = reader["class_id"];
                    data["academic_year_id"] = reader["academic_year_id"];
                    data["academic_year_name"] = reader["academic_year_name"];
                    data["teacher_npk"] = reader["teacher_npk"];
                    data["parent_name"] = reader["parent_name"];
                    data["academic_class_id"] = reader["academic_class_id"];

                    classId = reader["academic_class_id"].ToString();
                }

                reader.Close();

                // attendance
                var attendanceCmd = new SqlCommand(@"
                    SELECT 
                        SUM(CASE WHEN d.status='SICK' THEN 1 ELSE 0 END) AS sick,
                        SUM(CASE WHEN d.status='EXCUSED' THEN 1 ELSE 0 END) AS permit,
                        SUM(CASE WHEN d.status='NOINFO' THEN 1 ELSE 0 END) AS alpha
                    FROM txn_attendance_details d
                    JOIN txn_attendances a 
                        ON a.attendance_id = d.attendance_id
                    WHERE d.student_id = @studentId
                    AND a.academic_class_id = @classId
                ", conn);

                attendanceCmd.Parameters.AddWithValue("@studentId", id);
                attendanceCmd.Parameters.AddWithValue("@classId", classId);

                var attReader = attendanceCmd.ExecuteReader();

                if (attReader.Read())
                {
                    data["sick"] = Convert.ToInt32(attReader["sick"]);
                    data["permit"] = Convert.ToInt32(attReader["permit"]);
                    data["alpha"] = Convert.ToInt32(attReader["alpha"]);
                }

                attReader.Close();
                var headCmd = new SqlCommand(@"
                    SELECT detail_id, item_desc
                    FROM mst_detail_setting_landingpages
                    WHERE detail_id IN ('ABOUT_HEADSC_NAME1', 'HOME_SCHOOL_TITLE1', 'ABOUT_HEADSC_NPK1')
                ", conn);

                var headReader = headCmd.ExecuteReader();

                while (headReader.Read())
                {
                    var key = headReader["detail_id"].ToString();
                    var value = headReader["item_desc"]?.ToString() ?? "";

                    if (key == "ABOUT_HEADSC_NAME1")
                        data["headschool_name"] = value;

                    if (key == "HOME_SCHOOL_TITLE1")
                        data["school_name"] = value;

                    if (key == "ABOUT_HEADSC_NPK1")
                        data["headschool_npk"] = value;
                }

                headReader.Close();

                data["report"] = GetStudentReport(id, classId);
            }

            return View("~/Views/PortalAdmin/E-Raport/EraportPdf.cshtml", data);
        }

        private int GetNextCounter(object lastIdObj, int prefixLength)
        {
            if (lastIdObj == null) return 1;

            string lastId = lastIdObj.ToString();

            if (lastId.Length <= prefixLength) return 1;

            int number;
            if (!int.TryParse(lastId.Substring(prefixLength), out number))
                return 1;

            return number + 1;
        }

        [HttpPost]
        public IActionResult SaveHeader(string student_id, string student_name, string nis, string class_id, string academic_class_id,
            string class_name, string semester, string academic_year_id, string academic_year_name, string school_name,
            string school_address, string teacher_npk, string teacher_name, string headschool_name, string headschool_npk,
            string parent_name, string wali_notes, string parent_notes, string kokurikuler, string grades, string extracurriculars)
        {
            using (var conn = GetConn())
            {
                conn.Open();
                var getLastIdCmdEr = new SqlCommand(@"
                    SELECT TOP 1 eraport_id 
                    FROM txn_eraports
                    ORDER BY eraport_id DESC
                ", conn);

                var lastIdObjEr = getLastIdCmdEr.ExecuteScalar();

                string newIdEr = "ERP001";

                if (lastIdObjEr != null)
                {
                    string lastId = lastIdObjEr.ToString();
                    int number = int.Parse(lastId.Substring(3));
                    number++;
                    newIdEr = "ERP" + number.ToString("D3");
                }

                var gradeList = Newtonsoft.Json.JsonConvert
                    .DeserializeObject<List<dynamic>>(grades);
                var extraList = Newtonsoft.Json.JsonConvert
                    .DeserializeObject<List<dynamic>>(extracurriculars);
                //var attList = Newtonsoft.Json.JsonConvert
                //    .DeserializeObject<List<dynamic>>(attendances);
                var transaction = conn.BeginTransaction();

                try
                {
                    var cmdHr = new SqlCommand(@"
                        INSERT INTO txn_eraports
                        (eraport_id, student_id, student_name, nis, class_id, class_name, semester, 
                        academic_year_id, academic_year_name, school_name, school_address, homeroom_teacher_npk, 
                        homeroom_teacher_name, headschool_name, headschool_npk, parent_name, homeroom_teacher_notes, 
                        parent_notes, kokurikuler, created_at)
                        VALUES
                        (@eraport_id, @student_id, @student_name, @nis, @class_id, @class_name, @semester, 
                        @academic_year_id, @academic_year_name, @school_name, @school_address, @homeroom_teacher_npk, 
                        @homeroom_teacher_name, @headschool_name, @headschool_npk, @parent_name, @homeroom_teacher_notes, 
                        @parent_notes, @kokurikuler, GETDATE())
                    ", conn, transaction);

                    cmdHr.Parameters.AddWithValue("@eraport_id", newIdEr);
                    cmdHr.Parameters.AddWithValue("@student_id", student_id);
                    cmdHr.Parameters.AddWithValue("@student_name", student_name);
                    cmdHr.Parameters.AddWithValue("@nis", nis);
                    cmdHr.Parameters.AddWithValue("@class_id", class_id);
                    cmdHr.Parameters.AddWithValue("@class_name", class_name);
                    cmdHr.Parameters.AddWithValue("@semester", semester);
                    cmdHr.Parameters.AddWithValue("@academic_year_id", academic_year_id);
                    cmdHr.Parameters.AddWithValue("@academic_year_name", academic_year_name);
                    cmdHr.Parameters.AddWithValue("@school_name", school_name);
                    cmdHr.Parameters.AddWithValue("@school_address", school_address);
                    cmdHr.Parameters.AddWithValue("@homeroom_teacher_npk", teacher_npk);
                    cmdHr.Parameters.AddWithValue("@homeroom_teacher_name", teacher_name);
                    cmdHr.Parameters.AddWithValue("@headschool_name", headschool_name);
                    cmdHr.Parameters.AddWithValue("@headschool_npk", headschool_npk);
                    cmdHr.Parameters.AddWithValue("@parent_name", parent_name);
                    cmdHr.Parameters.AddWithValue("@homeroom_teacher_notes", wali_notes ?? "");
                    cmdHr.Parameters.AddWithValue("@parent_notes", parent_notes ?? "");
                    cmdHr.Parameters.AddWithValue("@kokurikuler", kokurikuler ?? "");
                    cmdHr.ExecuteNonQuery();

                    var getLastIdCmdErg = new SqlCommand(@"
                        SELECT TOP 1 eraport_grade_id 
                        FROM txn_eraport_grades
                        ORDER BY eraport_grade_id DESC
                    ", conn, transaction);

                    var lastIdObjErg = getLastIdCmdErg.ExecuteScalar();
                    int gradeCounter = GetNextCounter(lastIdObjErg, 4);

                    foreach (var g in gradeList)
                    {
                        if (string.IsNullOrWhiteSpace((string)g.subject_id))
                            continue;
                        if (string.IsNullOrWhiteSpace((string)g.subject_name))
                            continue;
                        if (string.IsNullOrWhiteSpace((string)g.subject_type_id))
                            continue;
                        if (string.IsNullOrWhiteSpace((string)g.subject_type_name))
                            continue;

                        var cmd = new SqlCommand(@"
                            INSERT INTO txn_eraport_grades
                            (eraport_grade_id, eraport_id, subject_id, subject_name, subject_type_id, subject_type_name, final_score_rps, final_score_adjustment, predicate, competency_description, created_at)
                            VALUES
                            (@eraport_grade_id, @eraport_id, @subject_id, @subject_name, @subject_type_id, @subject_type_name, @final_score_rps, @final_score_adjustment, @predicate, @competency_description, GETDATE())
                        ", conn, transaction);

                        string newIdErgLoop = "ERPG" + gradeCounter.ToString("D3");
                        cmd.Parameters.AddWithValue("@eraport_grade_id", newIdErgLoop);
                        cmd.Parameters.AddWithValue("@eraport_id", newIdEr);
                        cmd.Parameters.AddWithValue("@subject_id", (string)g.subject_id);
                        cmd.Parameters.AddWithValue("@subject_name", (string)g.subject_name);
                        cmd.Parameters.AddWithValue("@subject_type_id", (string)g.subject_type_id);
                        cmd.Parameters.AddWithValue("@subject_type_name", (string)g.subject_type_name);
                        cmd.Parameters.AddWithValue("@final_score_rps", g.final_score_rps?.ToString() ?? "0");
                        cmd.Parameters.AddWithValue("@final_score_adjustment", g.final_score_adjustment?.ToString() ?? "0");
                        cmd.Parameters.AddWithValue("@predicate", g.predicate?.ToString() ?? "");
                        cmd.Parameters.AddWithValue("@competency_description", g.competency_description?.ToString() ?? "");
                        gradeCounter++;

                        cmd.ExecuteNonQuery();
                    }

                    var getLastIdCmdExtra = new SqlCommand(@"
                        SELECT TOP 1 eraport_extracurricular_id 
                        FROM txn_eraport_extracurriculars
                        ORDER BY eraport_extracurricular_id DESC
                    ", conn, transaction);

                    var lastIdObjExtra = getLastIdCmdExtra.ExecuteScalar();
                    int extraCounter = GetNextCounter(lastIdObjExtra, 4);

                    var getLastExtIdCmd = new SqlCommand(@"
                        SELECT TOP 1 extracurricular_id 
                        FROM txn_eraport_extracurriculars
                        WHERE extracurricular_id IS NOT NULL
                        ORDER BY extracurricular_id DESC
                    ", conn, transaction);

                    var lastExtIdObj = getLastExtIdCmd.ExecuteScalar();
                    int extCounter = GetNextCounter(lastExtIdObj, 3);

                    foreach (var ex in extraList)
                    {
                        string name = ex.extracurricular_name?.ToString();
                        string predicate = ex.predicate?.ToString();
                        string desc = ex.description?.ToString();

                        if (string.IsNullOrWhiteSpace(name))
                            continue;
                        if (string.IsNullOrWhiteSpace(predicate))
                            continue;
                        if (string.IsNullOrWhiteSpace(desc))
                            continue;

                        var cmdExtra = new SqlCommand(@"
                            INSERT INTO txn_eraport_extracurriculars
                            (eraport_extracurricular_id, eraport_id, extracurricular_id, extracurricular_name, predicate, description, created_at)
                            VALUES
                            (@eraport_extracurricular_id, @eraport_id, @extracurricular_id, @extracurricular_name, @predicate, @description, GETDATE())
                        ", conn, transaction);

                        string newIdExtra = "ERPE" + extraCounter.ToString("D3");
                        string newExtId = "EXT" + extCounter.ToString("D3");
                        cmdExtra.Parameters.AddWithValue("@eraport_extracurricular_id", newIdExtra);
                        cmdExtra.Parameters.AddWithValue("@eraport_id", newIdEr);
                        cmdExtra.Parameters.AddWithValue("@extracurricular_id", newExtId);
                        cmdExtra.Parameters.AddWithValue("@extracurricular_name", name);
                        cmdExtra.Parameters.AddWithValue("@predicate", predicate);
                        cmdExtra.Parameters.AddWithValue("@description", desc);
                        cmdExtra.ExecuteNonQuery();

                        extraCounter++;
                        extCounter++;
                    }

                    var attendanceSummaryCmd = new SqlCommand(@"
                        SELECT 
                            SUM(CASE WHEN d.status='SICK' THEN 1 ELSE 0 END) AS sick,
                            SUM(CASE WHEN d.status='EXCUSED' THEN 1 ELSE 0 END) AS permit,
                            SUM(CASE WHEN d.status='NOINFO' THEN 1 ELSE 0 END) AS alpha
                        FROM txn_attendance_details d
                        JOIN txn_attendances a 
                            ON a.attendance_id = d.attendance_id
                        WHERE d.student_id = @studentId
                        AND a.academic_class_id = @classId
                    ", conn, transaction);

                    attendanceSummaryCmd.Parameters.AddWithValue("@studentId", student_id);
                    attendanceSummaryCmd.Parameters.AddWithValue("@classId", academic_class_id);

                    var readerAtt = attendanceSummaryCmd.ExecuteReader();

                    int sick = 0, permit = 0, alpha = 0;

                    if (readerAtt.Read())
                    {
                        sick = readerAtt["sick"] != DBNull.Value ? Convert.ToInt32(readerAtt["sick"]) : 0;
                        permit = readerAtt["permit"] != DBNull.Value ? Convert.ToInt32(readerAtt["permit"]) : 0;
                        alpha = readerAtt["alpha"] != DBNull.Value ? Convert.ToInt32(readerAtt["alpha"]) : 0;
                    }

                    readerAtt.Close();

                    var attendanceList = new List<(string name, int total)>
                    {
                        ("Sakit", sick),
                        ("Izin", permit),
                        ("Tanpa Keterangan", alpha)
                    };

                    var getLastIdCmdAtt = new SqlCommand(@"
                        SELECT TOP 1 eraport_attendance_id 
                        FROM txn_eraport_attendances
                        ORDER BY eraport_attendance_id DESC
                    ", conn, transaction);

                    var lastIdObjAtt = getLastIdCmdAtt.ExecuteScalar();
                    int attCounter = GetNextCounter(lastIdObjAtt, 4);

                    var getLastTypeIdCmd = new SqlCommand(@"
                        SELECT TOP 1 attendance_type_id 
                        FROM txn_eraport_attendances
                        WHERE attendance_type_id IS NOT NULL
                        ORDER BY attendance_type_id DESC
                    ", conn, transaction);

                    var lastTypeIdObj = getLastTypeIdCmd.ExecuteScalar();
                    int typeCounter = GetNextCounter(lastTypeIdObj, 3);

                    foreach (var item in attendanceList)
                    {
                        string newIdAtt = "ERPA" + attCounter.ToString("D3");
                        string newAttTypeId = "ATT" + typeCounter.ToString("D3");

                        var cmdAtt = new SqlCommand(@"
                            INSERT INTO txn_eraport_attendances
                            (eraport_attendance_id, eraport_id, attendance_type_id, attendance_type_name, total_days, created_at)
                            VALUES
                            (@eraport_attendance_id, @eraport_id, @attendance_type_id, @attendance_type_name, @total_days, GETDATE())
                        ", conn, transaction);

                        cmdAtt.Parameters.AddWithValue("@eraport_attendance_id", newIdAtt);
                        cmdAtt.Parameters.AddWithValue("@eraport_id", newIdEr);
                        cmdAtt.Parameters.AddWithValue("@attendance_type_id", newAttTypeId);
                        cmdAtt.Parameters.AddWithValue("@attendance_type_name", item.name);
                        cmdAtt.Parameters.AddWithValue("@total_days", item.total);

                        cmdAtt.ExecuteNonQuery();

                        attCounter++;
                        typeCounter++;
                    }

                    transaction.Commit();
                }
                catch (Exception ex)
                {
                    transaction.Rollback();
                    return StatusCode(500, ex.Message);
                }
            }

            return Json(new { success = true });
        }
    }
}
