<?php 
include __DIR__ . '/db_connect.php';
session_start();

if (!isset($_SESSION['logged_in'])) { header("Location: SignIn.php"); exit(); }

$stmt = $const->prepare("SELECT * FROM shop WHERE shop_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();

$displayImg = (!empty($shop['shop_img_path'])) ? $shop['shop_img_path'] : 'profile_img/default.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eLibrary - Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="main-header">
        <h1>eLibrary</h1>
        <input type="checkbox" id="menu-toggle" class="menu-toggle">
        <label for="menu-toggle" class="burger-icon">
            <span></span><span></span><span></span>
        </label>
        <nav class="main-nav">
            <ul>
                <li><a href="Homepage.php">Homepage</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="signout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="owner-info">
            <div class="owner-text-content">
                <div id="profile-view">
                    <h3>Meet the Founder, <?php echo htmlspecialchars($shop['shop_owner']) ?></h3>
                    <p><?php echo htmlspecialchars($shop['shop_history']) ?></p>
                </div>
                <div id="profile-edit" style="display:none;"> 
                    <input type="text" id="edit-owner" value="<?php echo htmlspecialchars($shop['shop_owner']); ?>">
                    <textarea id="edit-history" style="height:150px;"><?php echo htmlspecialchars($shop['shop_history']); ?></textarea>
                </div>
            </div>

            <div class="owner-image-box"> 
                <img src="<?php echo htmlspecialchars($displayImg) ?>" id="profileImg" class="owner-image"> 
                <input type="file" id="imgInput" style="display:none;"> 
                <button id="changeImgBtn" class="edit-btn" style="display:none; width:100%; margin-top:10px;">Change Photo</button> 
            </div>
        </section>

        <div class="util">
            <button id="editProfileBtn" class="edit-btn">Edit Profile</button> 
            <button id="saveProfileBtn" class="save-button save" style="display:none;">Save Changes</button> 
        </div>
    </main>

    <script>
        const editBtn = document.getElementById("editProfileBtn");
        const saveBtn = document.getElementById("saveProfileBtn");
        const changeImgBtn = document.getElementById("changeImgBtn");
        const imgInput = document.getElementById("imgInput");

        editBtn.onclick = () => {
            document.getElementById("profile-view").style.display = "none";
            document.getElementById("profile-edit").style.display = "block";
            changeImgBtn.style.display = "block";
            editBtn.style.display = "none";
            saveBtn.style.display = "inline-block";
        };

        changeImgBtn.onclick = () => imgInput.click();
        imgInput.onchange = () => {
            const reader = new FileReader();
            reader.onload = (e) => document.getElementById("profileImg").src = e.target.result;
            reader.readAsDataURL(imgInput.files[0]);
        };

        saveBtn.onclick = async () => {
            const formData = new FormData();
            formData.append("owner", document.getElementById("edit-owner").value);
            formData.append("history", document.getElementById("edit-history").value);
            if(imgInput.files[0]) formData.append("profile_img", imgInput.files[0]);

            const res = await fetch("profile/update_profile.php", { method: "POST", body: formData });
            const data = await res.json();
            if(data.success) location.reload();
        };
    </script>
</body>
</html>