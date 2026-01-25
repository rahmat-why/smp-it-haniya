namespace Haniya.Models
{
    public class DTOResponse
    {
        public int code { get; set; } = 200;
        public bool success { get; set; } = true;
        public string message { get; set; }
        public object data { get; set; }

        public static DTOResponse ok(object data = null, string message = "success")
        {
            return new DTOResponse
            {
                code = 200,
                success = true,
                message = message,
                data = data
            };
        }

        public static DTOResponse fail(string message, int code = 400)
        {
            return new DTOResponse
            {
                code = code,
                success = false,
                message = message,
                data = null
            };
        }
    }
}