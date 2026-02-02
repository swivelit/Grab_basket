$gradleUrl = "https://services.gradle.org/distributions/gradle-8.4-bin.zip"
$outputFile = "gradle.zip"
$destination = "gradle"

Write-Host "Downloading Gradle..."
Invoke-WebRequest -Uri $gradleUrl -OutFile $outputFile

Write-Host "Extracting Gradle..."
Expand-Archive -Path $outputFile -DestinationPath $destination -Force

Write-Host "Done. Gradle is in $PWD\gradle\gradle-8.4"
