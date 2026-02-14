using Haniya.Models;
using Microsoft.AspNetCore.Mvc;
using System.Data.SqlClient;

namespace Haniya.Controllers.PortalStudent
{
    public class StGradeController : Controller
    {
        private readonly IConfiguration _config;
        public StGradeController(IConfiguration config) => _config = config;

        private SqlConnection GetConn() => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StGrade/Index.cshtml");
        }

        [HttpGet]
        public IActionResult GetMyGrade(string student_id)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(student_id))
                    return Json(DTOResponse.fail("Invalid student ID", 400));

                using var conn = GetConn();
                conn.Open();

                var sql = @"
            SELECT
                g.grade_id,
                g.grade_date,
                c.class_name,
                s.subject_name,
                CONCAT(t.first_name,' ',t.last_name) AS teacher_name,
                COALESCE(dt.item_desc, g.grade_type) AS grade_type_desc,
                s.minimum_value,
                d.grade_value,
                d.notes,
                d.grade_detail_id
            FROM txn_grade_details d
            JOIN txn_grades g ON d.grade_id = g.grade_id
            LEFT JOIN mst_academic_classes ac ON g.academic_class_id = ac.academic_class_id
            LEFT JOIN mst_classes c ON ac.class_id = c.class_id
            LEFT JOIN mst_subjects s ON g.subject_id = s.subject_id
            LEFT JOIN mst_teachers t ON g.teacher_id = t.teacher_id
            LEFT JOIN mst_detail_settings dt ON g.grade_type = dt.detail_id AND dt.header_id = 'GRADE_TYPE'
            WHERE d.student_id = @studentId
            ORDER BY d.created_at DESC, s.subject_name";

                var list = new List<object>();

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", student_id);

                    using var r = cmd.ExecuteReader();
                    while (r.Read())
                    {
                        list.Add(new
                        {
                            grade_id = r["grade_id"].ToString(),
                            grade_date = r["grade_date"] == DBNull.Value ? null : ((DateTime)r["grade_date"]).ToString("yyyy-MM-dd"),
                            class_name = r["class_name"]?.ToString(),
                            subject_name = r["subject_name"]?.ToString(),
                            teacher_name = r["teacher_name"]?.ToString(),
                            grade_type_desc = r["grade_type_desc"]?.ToString(),
                            minimum_value = r["minimum_value"] as double?,
                            grade_value = r["grade_value"]?.ToString(),
                            notes = r["notes"]?.ToString(),
                            grade_detail_id = r["grade_detail_id"]?.ToString()
                        });
                    }
                }

                return Json(DTOResponse.ok(list));
            }
            catch (Exception ex)
            {
                return Json(DTOResponse.fail(ex.Message, 500));
            }
        }
    }
}
