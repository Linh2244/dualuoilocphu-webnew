<?php
// Lớp phòng thủ phụ: nếu thư mục này vẫn nằm trong web root, chặn liệt kê nội dung
http_response_code(404);
exit;
