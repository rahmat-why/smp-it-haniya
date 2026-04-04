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

        [HttpGet]
        public IActionResult ExportPdf(string id, string classId)
        {
            if (string.IsNullOrWhiteSpace(id) || string.IsNullOrWhiteSpace(classId))
            {
                return BadRequest("Parameter id/classId wajib diisi.");
            }

            Dictionary<string, object> data = new Dictionary<string, object>();

            using (var conn = GetConn())
            {
                conn.Open();

                var cmd = new SqlCommand(@"
                    SELECT 
                        s.student_id,
                        s.nis,
                        s.full_name,
                        s.address,
                        c.class_name,
                        ac.academic_class_id,
                        ay.semester,
                        ay.start_date,
                        ay.end_date,
                        t.full_name AS teacher_name
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
                    data["address"] = reader["address"];
                    data["semester"] = reader["semester"];
                    data["start_date"] = reader["start_date"];
                    data["end_date"] = reader["end_date"];
                    data["teacher_name"] = reader["teacher_name"];

                    classId = reader["academic_class_id"].ToString();
                }
                else
                {
                    return NotFound("Data student tidak ditemukan.");
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
                    data["sick"] = SafeToInt(attReader["sick"]);
                    data["permit"] = SafeToInt(attReader["permit"]);
                    data["alpha"] = SafeToInt(attReader["alpha"]);
                }
                else
                {
                    data["sick"] = 0;
                    data["permit"] = 0;
                    data["alpha"] = 0;
                }

                attReader.Close();

                // ambil nilai raport
                data["report"] = GetStudentReport(id, classId);
            }

            return new ViewAsPdf("~/Views/PortalAdmin/E-Raport/EraportPdf.cshtml", data)
            {
                PageSize = Rotativa.AspNetCore.Options.Size.A4,
                CustomSwitches = "--print-media-type --disable-smart-shrinking"

            };
        }

        public DataTable GetStudentReport(string studentId, string classId)
        {
            using var conn = GetConn();
            conn.Open();

            string query = @"
                SELECT 
                    s.subject_name,

                    a.attendance_score,

                    (
                        (ISNULL(a.attendance_score,0) * r.weight_attendance / 100.0) +
                        (AVG(CASE WHEN g.grade_type = 'GRADE_TASK' THEN gd.grade_value END) * r.weight_task / 100.0) +
                        (AVG(CASE WHEN g.grade_type = 'GRADE_TEST' THEN gd.grade_value END) * r.weight_uh / 100.0) +
                        (AVG(CASE WHEN g.grade_type = 'GRADE_MID' THEN gd.grade_value END) * r.weight_pts / 100.0) +
                        (AVG(CASE WHEN g.grade_type = 'GRADE_FINAL' THEN gd.grade_value END) * r.weight_pas / 100.0)
                    ) AS final_score

                FROM txn_grades g

                JOIN txn_grade_details gd 
                    ON g.grade_id = gd.grade_id

                JOIN mst_subjects s 
                    ON g.subject_id = s.subject_id

                JOIN mst_rps r 
                    ON r.subject_id = g.subject_id
                    AND r.academic_class_id = g.academic_class_id

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
                ON a.student_id = gd.student_id
                AND a.academic_class_id = g.academic_class_id

                WHERE gd.student_id = @studentId
                AND g.academic_class_id = @classId

                GROUP BY 
                    s.subject_name,
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

        [HttpGet]
        public IActionResult Preview(string id, string classId)
        {
            if (string.IsNullOrWhiteSpace(id) || string.IsNullOrWhiteSpace(classId))
            {
                return BadRequest("Parameter id/classId wajib diisi.");
            }

            Dictionary<string, object> data = new Dictionary<string, object>();

            using (var conn = GetConn())
            {
                conn.Open();

                var cmd = new SqlCommand(@"
            SELECT 
                s.student_id,
                s.nis,
                s.full_name,
                s.address,
                c.class_name,
                ac.academic_class_id,
                ay.semester,
                ay.start_date,
                ay.end_date,
                t.full_name AS teacher_name
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
                    data["address"] = reader["address"];
                    data["semester"] = reader["semester"];
                    data["start_date"] = reader["start_date"];
                    data["end_date"] = reader["end_date"];
                    data["teacher_name"] = reader["teacher_name"];

                    classId = reader["academic_class_id"].ToString();
                }
                else
                {
                    return NotFound("Data student tidak ditemukan.");
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
                    data["sick"] = SafeToInt(attReader["sick"]);
                    data["permit"] = SafeToInt(attReader["permit"]);
                    data["alpha"] = SafeToInt(attReader["alpha"]);
                }
                else
                {
                    data["sick"] = 0;
                    data["permit"] = 0;
                    data["alpha"] = 0;
                }

                attReader.Close();

                data["report"] = GetStudentReport(id, classId);
            }

            return View("~/Views/PortalAdmin/E-Raport/EraportPdf.cshtml", data);
        }

    }
}
