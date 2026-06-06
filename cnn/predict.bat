@echo off
setlocal enabledelayedexpansion

REM Set home directory (required for TensorFlow)
set HOME=%USERPROFILE%
set USERPROFILE=%USERPROFILE%

REM Set environment variables for TensorFlow
set TF_CPP_MIN_LOG_LEVEL=3
set TF_ENABLE_ONEDNN_OPTS=0
set TF_FORCE_GPU_ALLOW_GROWTH=true
set CUDA_VISIBLE_DEVICES=
set OMP_NUM_THREADS=1

REM Set TensorFlow cache to a temp directory
set TF_HOME=%TMPDIR%
if "%TF_HOME%"=="" set TF_HOME=%TEMP%

REM Get the directory where this batch file is located
set SCRIPT_DIR=%~dp0
set PREDICT_SCRIPT=%SCRIPT_DIR%predict.py

REM Use full Python path with all packages installed
"C:\Users\ankOYaRa\AppData\Local\Python\pythoncore-3.11-64\python.exe" "%PREDICT_SCRIPT%" "%~1"
