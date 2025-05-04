<?php
session_start();
session_unset();
session_destroy();
echo "<script>
    sessionStorage.setItem('loggedIn', 'false');
    window.location.href = 'index.php';
</script>";
exit();
?>
