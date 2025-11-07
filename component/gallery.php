<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login_index.html');
    exit;
}

session_regenerate_id(true);

if (filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING) && filter_input(INPUT_GET, 'titulo', FILTER_SANITIZE_STRING)) {
    $inspectionId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    $titulo = filter_input(INPUT_GET, 'titulo', FILTER_SANITIZE_STRING);
    $tituloFormatted = str_replace(' ', '_', $titulo);
} else {
    echo "Error: ID de inspección o título no proporcionado.";
    exit;
}

$rootDir = dirname(__DIR__);
$folderPath = $rootDir . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "photo_gallery" . DIRECTORY_SEPARATOR . $inspectionId . DIRECTORY_SEPARATOR . $tituloFormatted;

class GalleryManager {
    private $folderPath;

    public function __construct($folderPath) {
        $this->folderPath = $folderPath;
    }

    public function uploadPhotos($files) {
        $responses = [];
        foreach ($files['tmp_name'] as $key => $tmpName) {
            $fileName = basename($files['name'][$key]);
            $targetFilePath = $this->folderPath . DIRECTORY_SEPARATOR . $fileName;
            if (!file_exists($this->folderPath)) {
                mkdir($this->folderPath, 0777, true);
            }
            if (move_uploaded_file($tmpName, $targetFilePath)) {
                $responses[] = ['success' => true, 'message' => 'Foto subida con éxito: ' . htmlspecialchars($fileName)];
            } else {
                $responses[] = ['success' => false, 'message' => 'Error: No se pudo subir la foto: ' . htmlspecialchars($fileName)];
            }
        }
        return $responses;
    }

    public function deletePhoto($photoToDelete) {
        $photoPath = filter_var($photoToDelete, FILTER_SANITIZE_STRING);
        if (file_exists($photoPath)) {
            unlink($photoPath);
            return ['success' => true, 'message' => 'Foto eliminada con éxito: ' . htmlspecialchars($photoPath)];
        } else {
            return ['success' => false, 'message' => 'Error: No se pudo eliminar la foto: ' . htmlspecialchars($photoPath)];
        }
    }

    public function getPhotos() {
        if (is_dir($this->folderPath)) {
            return array_diff(scandir($this->folderPath, SCANDIR_SORT_DESCENDING), ['.', '..']);
        } else {
            return [];
        }
    }
}

$galleryManager = new GalleryManager($folderPath);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['photos'])) {
        $responses = $galleryManager->uploadPhotos($_FILES['photos']);
        echo json_encode($responses);
        exit;
    } elseif (isset($_POST['delete_photo'])) {
        $response = $galleryManager->deletePhoto($_POST['delete_photo']);
        echo json_encode($response);
        exit;
    }
}

$photos = $galleryManager->getPhotos();

$lastPage = isset($_SESSION['last_page']) ? $_SESSION['last_page'] : '#';
$urlParts = parse_url($lastPage);
parse_str($urlParts['query'] ?? '', $queryParams);
if (!isset($queryParams['id'])) {
    $lastPage .= (strpos($lastPage, '?') === false ? '?' : '&') . 'id=' . urlencode($inspectionId);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?> Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            justify-items: center;
            padding: 10px;
        }

        .gallery img {
            cursor: pointer;
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .gallery img:hover {
            transform: scale(1.05);
        }

        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .lightbox img {
            max-width: 90%;
            max-height: 80%;
        }

        .lightbox .prev,
        .lightbox .next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 2em;
            cursor: pointer;
            user-select: none;
        }

        .lightbox .prev {
            left: 10px;
        }

        .lightbox .next {
            right: 10px;
        }

        .lightbox .close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2em;
            cursor: pointer;
        }

        .progress-container {
            width: 100%;
            height: 20px;
            background: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar {
            width: 0;
            height: 100%;
            background: #4caf50;
            transition: width 0.4s ease;
        }

        .progress-text {
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto mt-8 px-4">
        <div class="flex mb-8 items-center">
            <a href="#" id="backButton" class="inline-block p-4 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-lg mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($titulo); ?> Gallery</h1>
        </div>

        <div class="flex justify-end mb-8">
            <button id="uploadButton" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Upload Photos
            </button>
        </div>

        <div id="progressContainer" class="progress-container" style="display: none;">
            <div id="progressBar" class="progress-bar"></div>
            <div id="progressText" class="progress-text">0%</div>
        </div>
        <div class="gallery">
            <?php foreach ($photos as $photo) : ?>
                <div class="relative">
                    <img src="<?php echo '../img/photo_gallery/' . $inspectionId . '/' . $tituloFormatted . '/' . $photo; ?>" alt="Photo" loading="lazy">
                    <form method="POST" class="absolute top-2 right-2 delete-photo-form">
                        <input type="hidden" name="delete_photo" value="<?php echo htmlspecialchars($folderPath . DIRECTORY_SEPARATOR . $photo); ?>">
                        <button type="button" class="delete-button text-red-500 bg-white rounded-full p-1">✖</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (count($photos) === 0) : ?>
                <p id="emptyGalleryMessage" class="text-center text-gray-500">The photo gallery is empty.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="lightbox flex">
        <span class="close">✖</span>
        <span class="prev">❮</span>
        <img src="" alt="Large Photo">
        <span class="next">❯</span>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const backButton = document.getElementById('backButton');
            const galleryPage = window.location.href;

            if (document.referrer && document.referrer !== galleryPage) {
                localStorage.setItem('lastPage', document.referrer);
            }

            backButton.addEventListener('click', (event) => {
                event.preventDefault();
                const lastPage = localStorage.getItem('lastPage');
                if (lastPage) {
                    window.location.href = lastPage;
                    localStorage.removeItem('lastPage');
                } else {
                    window.history.back();
                }
            });
        });

        const gallery = document.querySelector('.gallery');
        const lightbox = document.querySelector('.lightbox');
        const lightboxImage = lightbox.querySelector('img');
        const prevButton = lightbox.querySelector('.prev');
        const nextButton = lightbox.querySelector('.next');
        const closeButton = lightbox.querySelector('.close');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        let currentIndex = 0;
        let photos = [];

        lightbox.style.display = 'none';

        gallery.querySelectorAll('img').forEach((img, index) => {
            photos.push(img.src);
            img.addEventListener('click', () => {
                currentIndex = index;
                showLightbox();
            });
        });

        function showLightbox() {
            lightboxImage.src = photos[currentIndex];
            lightbox.style.display = 'flex';
        }

        function hideLightbox() {
            lightbox.style.display = 'none';
        }

        function showPrevPhoto() {
            currentIndex = (currentIndex > 0) ? currentIndex - 1 : photos.length - 1;
            lightboxImage.src = photos[currentIndex];
        }

        function showNextPhoto() {
            currentIndex = (currentIndex < photos.length - 1) ? currentIndex + 1 : 0;
            lightboxImage.src = photos[currentIndex];
        }

        prevButton.addEventListener('click', showPrevPhoto);
        nextButton.addEventListener('click', showNextPhoto);
        closeButton.addEventListener('click', hideLightbox);
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                hideLightbox();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                showPrevPhoto();
            } else if (e.key === 'ArrowRight') {
                showNextPhoto();
            } else if (e.key === 'Escape') {
                hideLightbox();
            }
        });

        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm("Are you sure you want to delete this image?")) {
                    const form = this.closest('form');
                    const formData = new FormData(form);

                    fetch('', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                form.closest('.relative').remove();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });
        });

        document.getElementById('uploadButton').addEventListener('click', () => {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.multiple = true;

            fileInput.onchange = e => {
                const files = e.target.files;
                if (!files.length) return;

                const formData = new FormData();
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = event => {
                        const img = new Image();
                        img.onload = () => {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');

                            const maxWidth = 3840;
                            const maxHeight = 2160;
                            let width = img.width;
                            let height = img.height;

                            if (width > height) {
                                if (width > maxWidth) {
                                    height = Math.round((height *= maxWidth / width));
                                    width = maxWidth;
                                }
                            } else {
                                if (height > maxHeight) {
                                    width = Math.round((width *= maxHeight / height));
                                    height = maxHeight;
                                }
                            }

                            canvas.width = width;
                            canvas.height = height;
                            ctx.drawImage(img, 0, 0, width, height);

                            canvas.toBlob(blob => {
                                formData.append('photos[]', blob, file.name);
                                formData.append('inspectionId', '<?php echo $inspectionId; ?>');

                                if (formData.getAll('photos[]').length === files.length) {
                                    progressContainer.style.display = 'block';

                                    const xhr = new XMLHttpRequest();
                                    xhr.open('POST', window.location.href, true);
                                    xhr.upload.onprogress = (event) => {
                                        if (event.lengthComputable) {
                                            const percentComplete = (event.loaded / event.total) * 100;
                                            progressBar.style.width = percentComplete + '%';
                                            progressText.textContent = Math.round(percentComplete) + '%';
                                        }
                                    };
                                    xhr.onload = () => {
                                        if (xhr.status === 200) {
                                            const result = JSON.parse(xhr.responseText);
                                            let error = false;
                                            result.forEach(res => {
                                                if (!res.success) {
                                                    alert(res.message);
                                                    error = true;
                                                }
                                            });
                                            if (!error) {
                                                alert('Photos uploaded successfully!');
                                                location.reload();
                                            }
                                        } else {
                                            alert('Error uploading photos');
                                        }
                                        progressContainer.style.display = 'none';
                                        progressBar.style.width = '0%';
                                        progressText.textContent = '0%';
                                    };
                                    xhr.onerror = () => {
                                        alert('Error uploading photos');
                                        progressContainer.style.display = 'none';
                                        progressBar.style.width = '0%';
                                        progressText.textContent = '0%';
                                    };
                                    xhr.send(formData);
                                }
                            }, 'image/jpeg', 0.99);
                        };
                        img.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                });
            };

            fileInput.click();
        });
    </script>
</body>

</html>
