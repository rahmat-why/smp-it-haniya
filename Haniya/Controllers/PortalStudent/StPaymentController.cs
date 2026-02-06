using Microsoft.AspNetCore.Mvc;
using System;
using System.Collections.Generic;
using System.Data.SqlClient;
using System.Security.Claims;
using Haniya.Models;

namespace Haniya.Controllers.PortalStudent
{
    public class StPaymentController : Controller
    {
        private readonly IConfiguration _config;

        public StPaymentController(IConfiguration config)
        {
            _config = config;
        }

        private SqlConnection GetConn()
            => new SqlConnection(_config.GetConnectionString("DefaultConnection"));

        // View
        public IActionResult Index()
        {
            return View("~/Views/PortalStudent/StPayment/Index.cshtml");
        }


        // Get payment list (by logged student)
        [HttpGet]
        public IActionResult GetMyPayments()
        {
            try
            {
                // Ambil StudentId dari login
                var studentId = User.FindFirst("StudentId")?.Value;

                if (string.IsNullOrEmpty(studentId))
                    return Json(DTOResponse.fail("Unauthorized", 401));


                using var conn = GetConn();
                conn.Open();

                var sql = @"
                SELECT
                    p.payment_id,
                    p.payment_type,
                    p.total_price,
                    p.total_payment,
                    p.remaining_payment,
                    p.status,
                    p.payment_date,
                    p.payment_method,

                    COALESCE(pt.item_desc, p.payment_type) AS payment_type_desc,
                    COALESCE(pm.item_desc, p.payment_method) AS payment_method_desc,

                    c.class_name

                FROM txn_payments p

                JOIN mst_student_classes sc 
                    ON p.student_class_id = sc.student_class_id

                JOIN mst_students s
                    ON sc.student_id = s.student_id

                LEFT JOIN mst_academic_classes ac
                    ON sc.academic_class_id = ac.academic_class_id

                LEFT JOIN mst_classes c
                    ON ac.class_id = c.class_id

                LEFT JOIN mst_detail_settings pt 
                    ON p.payment_type = pt.detail_id 
                   AND pt.header_id = 'PAYMENT_TYPE'

                LEFT JOIN mst_detail_settings pm 
                    ON p.payment_method = pm.detail_id 
                   AND pm.header_id = 'PAYMENT_METHOD'

                WHERE s.student_id = @studentId

                ORDER BY p.payment_date DESC
                ";


                var list = new List<object>();

                using (var cmd = new SqlCommand(sql, conn))
                {
                    cmd.Parameters.AddWithValue("@studentId", studentId);

                    using var r = cmd.ExecuteReader();

                    while (r.Read())
                    {
                        list.Add(new
                        {
                            payment_id = r["payment_id"]?.ToString(),

                            payment_type = r["payment_type_desc"]?.ToString(),

                            total_price = r["total_price"] as decimal?,
                            total_payment = r["total_payment"] as decimal?,
                            remaining_payment = r["remaining_payment"] as decimal?,

                            status = r["status"]?.ToString(),

                            payment_date = r["payment_date"] == DBNull.Value
                                ? null
                                : ((DateTime)r["payment_date"]).ToString("yyyy-MM-dd"),

                            payment_method = r["payment_method_desc"]?.ToString(),

                            class_name = r["class_name"]?.ToString()
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
