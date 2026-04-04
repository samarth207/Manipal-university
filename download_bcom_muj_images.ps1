# Download all BCom MUJ images
$ErrorActionPreference = "Continue"
$baseUpload = "https://www.onlinemanipal.com/wp-content/uploads"
$baseTheme = "https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images"

# Create directories
$dirs = @(
    "images\bcom-muj\faculty",
    "images\bcom-muj\testimonials",
    "images\bcom-muj\alumni",
    "images\bcom-muj\benefits",
    "images\bcom-muj\rankings",
    "images\bcom-muj\certificate",
    "images\bcom-muj\lms",
    "images\bcom-muj\blogs",
    "images\bcom-muj\coursera"
)
foreach ($d in $dirs) {
    if (!(Test-Path $d)) { New-Item -ItemType Directory -Path $d -Force | Out-Null }
}

$downloads = @(
    # Faculty
    @{ url="$baseUpload/2023/03/CA-Shaifali-Mathur-1.png"; path="images\bcom-muj\faculty\Shaifali-Mathur.png" },
    @{ url="$baseUpload/2023/03/dr-vandana-mishra.png"; path="images\bcom-muj\faculty\Vandna-Misra.png" },
    @{ url="$baseUpload/2023/03/sr-asha-mamraj-sharma.png"; path="images\bcom-muj\faculty\Asha-Mamraj-Sharma.png" },
    @{ url="$baseUpload/2023/03/Dr.-Nidhi-Vyas.png"; path="images\bcom-muj\faculty\Nidhi-Vyas.png" },
    @{ url="$baseUpload/2023/03/dr-ashish-gupta.png"; path="images\bcom-muj\faculty\Ashish-Gupta.png" },
    @{ url="$baseUpload/2023/03/dr-mredu-goyal.png"; path="images\bcom-muj\faculty\Mredu-Goyal.png" },
    @{ url="$baseUpload/2023/03/dr-neha-mathur.png"; path="images\bcom-muj\faculty\Neha-Mathur.png" },
    @{ url="$baseUpload/2023/03/dr-srinivas-iyer.png"; path="images\bcom-muj\faculty\Srinivasan-Iyer.png" },
    @{ url="$baseUpload/2023/03/dr-yogita-s-garwal.png"; path="images\bcom-muj\faculty\Yogita-Garwal.png" },
    @{ url="$baseUpload/2023/03/Mr.-Harsh-Nagar.png"; path="images\bcom-muj\faculty\Harsh-Nagar.png" },
    @{ url="$baseUpload/2023/03/manoj-kumar-yadav.png"; path="images\bcom-muj\faculty\Manoj-Kumar-Yadav.png" },
    @{ url="$baseUpload/2023/03/Mr.-Srikant-Dubey.png"; path="images\bcom-muj\faculty\Shri-Kant-Dubey.png" },
    @{ url="$baseUpload/2023/03/Ms.-Shivangi-Seth.png"; path="images\bcom-muj\faculty\Shivangi-Seth.png" },
    @{ url="$baseUpload/2023/03/yagnika-sharma.png"; path="images\bcom-muj\faculty\Yagnika-Sharma.png" },

    # Testimonials
    @{ url="$baseUpload/2024/10/Naman-sutaria.jpg"; path="images\bcom-muj\testimonials\Naman-Sutaria.jpg" },
    @{ url="$baseUpload/2023/08/Manjinder-1.jpg"; path="images\bcom-muj\testimonials\Manjinder-Pal.jpg" },
    @{ url="$baseUpload/2023/04/Velari-kalpana.png"; path="images\bcom-muj\testimonials\Velari-Kalpana.png" },
    @{ url="$baseUpload/2023/03/Sahana-K-Test.jpg"; path="images\bcom-muj\testimonials\Sahana-K.jpg" },

    # Alumni
    @{ url="$baseUpload/2022/11/Arjun-Deshmukh-%E2%80%93-BCom-.png"; path="images\bcom-muj\alumni\Arjun-Deshmukh.png" },

    # Program Benefits
    @{ url="$baseUpload/2022/11/Become-job-ready.jpg"; path="images\bcom-muj\benefits\become-job-ready.jpg" },
    @{ url="$baseUpload/2022/11/get-jobs-across-sectors.jpg"; path="images\bcom-muj\benefits\get-jobs-across-sectors.jpg" },
    @{ url="$baseUpload/2022/11/master-multi-desciplinary-practices.jpg"; path="images\bcom-muj\benefits\master-multi-disciplinary.jpg" },
    @{ url="$baseUpload/2023/06/robust-alumni-network-1.jpg"; path="images\bcom-muj\benefits\robust-alumni-network.jpg" },
    @{ url="$baseUpload/2023/06/Fully-online-experience-with-campus-immersions-1.jpg"; path="images\bcom-muj\benefits\fully-online-campus.jpg" },

    # Rankings
    @{ url="$baseUpload/2023/04/NIRF.jpg"; path="images\bcom-muj\rankings\NIRF.jpg" },
    @{ url="$baseUpload/2023/03/NAAC-A-2.jpg"; path="images\bcom-muj\rankings\NAAC-A.jpg" },
    @{ url="$baseUpload/2023/03/UGC-2.jpg"; path="images\bcom-muj\rankings\UGC.jpg" },
    @{ url="$baseUpload/2023/03/QS-Asia-1.png"; path="images\bcom-muj\rankings\QS-Asia.png" },
    @{ url="$baseUpload/2023/03/WES-2.jpg"; path="images\bcom-muj\rankings\WES.jpg" },
    @{ url="$baseUpload/2023/03/ACU-3.jpg"; path="images\bcom-muj\rankings\ACU.jpg" },
    @{ url="$baseUpload/2023/03/Career360-1.jpg"; path="images\bcom-muj\rankings\Career360.jpg" },
    @{ url="$baseUpload/2023/03/ICAS-1.jpg"; path="images\bcom-muj\rankings\ICAS.jpg" },
    @{ url="$baseUpload/2023/03/IQAS_Online-Manipal-Website.jpg"; path="images\bcom-muj\rankings\IQAS.jpg" },
    @{ url="$baseUpload/2023/03/zaqa-logo.jpg"; path="images\bcom-muj\rankings\ZAQA.jpg" },
    @{ url="$baseUpload/2024/05/Times-Higher-Education-Impact.jpg"; path="images\bcom-muj\rankings\THE.jpg" },
    @{ url="$baseUpload/2023/06/SMU_Rankings_The-Week.jpg"; path="images\bcom-muj\rankings\TheWeek.jpg" },

    # Certificate
    @{ url="$baseUpload/2022/11/MUJ-BCOM2.png"; path="images\bcom-muj\certificate\bcom-certificate-front.png" },
    @{ url="$baseUpload/2022/11/BCOM-scaled.png"; path="images\bcom-muj\certificate\bcom-certificate-back.png" },

    # MUJ Campus
    @{ url="$baseTheme/institution/seo-muj.webp"; path="images\bcom-muj\muj-campus.webp" },

    # Bloomberg
    @{ url="$baseTheme/boomberg.jpg"; path="images\bcom-muj\bloomberg.jpg" },

    # Foundation Courses
    @{ url="$baseTheme/tools-certificates/emerging-tech.png"; path="images\bcom-muj\emerging-tech.png" },
    @{ url="$baseTheme/business-leadership.png"; path="images\bcom-muj\business-leadership.png" },

    # LMS
    @{ url="$baseTheme/brightspace-lms/QuizMe.webp"; path="images\bcom-muj\lms\QuizMe.webp" },
    @{ url="$baseTheme/brightspace-lms/LearningPath.webp"; path="images\bcom-muj\lms\LearningPath.webp" },
    @{ url="$baseTheme/brightspace-lms/AIProfessor.webp"; path="images\bcom-muj\lms\AIProfessor.webp" },
    @{ url="$baseTheme/brightspace-lms/SummarizeMe.webp"; path="images\bcom-muj\lms\SummarizeMe.webp" },

    # Coursera logos
    @{ url="$baseUpload/2022/11/University-of-Illinois-at-Urbana-Champaign.svg"; path="images\bcom-muj\coursera\illinois.svg" },
    @{ url="$baseUpload/2022/11/Intuit.svg"; path="images\bcom-muj\coursera\intuit.svg" },
    @{ url="$baseUpload/2022/11/IESE-Business-School.svg"; path="images\bcom-muj\coursera\iese.svg" },
    @{ url="$baseUpload/2022/11/Johns-Hopkins-University.svg"; path="images\bcom-muj\coursera\johns-hopkins.svg" },
    @{ url="$baseUpload/2022/11/University-of-California-Irvine.svg"; path="images\bcom-muj\coursera\uc-irvine.svg" },
    @{ url="$baseUpload/2022/11/Google.svg"; path="images\bcom-muj\coursera\google.svg" },
    @{ url="$baseUpload/2022/11/Macquarie-University.svg"; path="images\bcom-muj\coursera\macquarie.svg" },

    # Hero image
    @{ url="$baseTheme/course-overview.png"; path="images\bcom-muj\course-overview.png" },

    # Blogs
    @{ url="$baseUpload/2023/06/BCOM-Job-ready-data-science-skills.jpg"; path="images\bcom-muj\blogs\blog1.jpg" },
    @{ url="$baseUpload/2024/04/Top-courses-you-can-pursue-along-with-online-BCom@2x-100-1.jpg"; path="images\bcom-muj\blogs\blog2.jpg" },

    # Course card images for bcom.html
    @{ url="https://www.onlinemanipal.com/wp-content/uploads/2025/03/BCOM-MUJ.png"; path="images\BCOM-MUJ.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/uploads/2025/07/BCOM@2x.webp"; path="images\BCOM-MAHE.webp" },
    @{ url="https://www.onlinemanipal.com/wp-content/uploads/2025/03/BCOM-SMU.png"; path="images\BCOM-SMU-new.png" },

    # Additional hiring partners 25-36
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-25.png"; path="images\hiring-partners\HP-25.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-26.png"; path="images\hiring-partners\HP-26.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-27.png"; path="images\hiring-partners\HP-27.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-28.png"; path="images\hiring-partners\HP-28.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-29.png"; path="images\hiring-partners\HP-29.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-30.png"; path="images\hiring-partners\HP-30.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-31.png"; path="images\hiring-partners\HP-31.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-32.png"; path="images\hiring-partners\HP-32.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-33.png"; path="images\hiring-partners\HP-33.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-34.png"; path="images\hiring-partners\HP-34.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-35.png"; path="images\hiring-partners\HP-35.png" },
    @{ url="https://www.onlinemanipal.com/wp-content/themes/flamingo/assets/images/placement-new/Hiring-Partners/HP-36.png"; path="images\hiring-partners\HP-36.png" }
)

$success = 0
$failed = 0
foreach ($item in $downloads) {
    try {
        if (!(Test-Path $item.path)) {
            Invoke-WebRequest -Uri $item.url -OutFile $item.path -UseBasicParsing -TimeoutSec 30
            $size = (Get-Item $item.path).Length
            if ($size -gt 0) {
                Write-Host "[OK] $($item.path) ($size bytes)"
                $success++
            } else {
                Remove-Item $item.path -Force
                Write-Host "[FAILED] $($item.path) - empty file"
                $failed++
            }
        } else {
            Write-Host "[SKIP] $($item.path) - already exists"
            $success++
        }
    } catch {
        Write-Host "[FAILED] $($item.path) - $($_.Exception.Message)"
        $failed++
    }
}
Write-Host "`nDone! Success: $success, Failed: $failed"
