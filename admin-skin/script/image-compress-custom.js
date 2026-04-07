/**
 * 이미지 압축 및 리사이징 유틸리티
 * PC/모바일 기준에 따라 자동으로 이미지를 압축하고 리사이징합니다.
 */

(function() {
    'use strict';

    /**
     * 이미지 파일인지 확인
     * @param {File} file - 확인할 파일
     * @returns {boolean}
     */
    function isImageFile(file) {
        if (!file || !file.type) {
            return false;
        }
        return /^image\/(jpeg|jpg|png|gif|webp|bmp)$/i.test(file.type);
    }

    /**
     * 이미지 압축이 필요한지 확인
     * @param {File} file - 확인할 파일
     * @returns {Promise<boolean>}
     */
    function shouldCompress(file) {
        return new Promise(function(resolve) {
            // 용량 기준 체크
            var sizeThreshold = 500 * 1024; // 500KB
            if (file.size < sizeThreshold) {
                resolve(false);
                return;
            }

            // 해상도 기준 체크
            var img = new Image();
            var objectUrl = URL.createObjectURL(file);
            
            img.onload = function() {
                URL.revokeObjectURL(objectUrl);
                var resolutionThreshold = 1920;
                var needsCompress = img.width >= resolutionThreshold || img.height >= resolutionThreshold;
                resolve(needsCompress);
            };
            
            img.onerror = function() {
                URL.revokeObjectURL(objectUrl);
                resolve(false);
            };
            
            img.src = objectUrl;
        });
    }

    /**
     * 이미지 리사이징
     * @param {HTMLImageElement} img - 원본 이미지
     * @param {number} maxWidth - 최대 너비
     * @param {number} maxHeight - 최대 높이
     * @returns {Object} {width, height}
     */
    function calculateDimensions(img, maxWidth, maxHeight) {
        var width = img.width;
        var height = img.height;

        // 비율 유지하면서 리사이징
        if (width > maxWidth || height > maxHeight) {
            var ratio = Math.min(maxWidth / width, maxHeight / height);
            width = Math.round(width * ratio);
            height = Math.round(height * ratio);
        }

        return { width: width, height: height };
    }

    /**
     * 목표 용량에 맞게 품질 조정하여 압축 (JPEG/WebP 지원)
     * @param {HTMLCanvasElement} canvas - 캔버스
     * @param {string} mimeType - MIME 타입
     * @param {number} quality - 초기 품질
     * @param {number} targetSize - 목표 용량 (bytes)
     * @returns {Promise<Blob>}
     */
    function compressToTargetSize(canvas, mimeType, quality, targetSize) {
        return new Promise(function(resolve, reject) {
            // JPEG 또는 WebP가 아니면 품질 조정 불가능, 리사이징만 수행
            var isJpeg = /^image\/(jpeg|jpg)$/i.test(mimeType);
            var isWebP = /^image\/webp$/i.test(mimeType);
            if (!isJpeg && !isWebP) {
                canvas.toBlob(function(blob) {
                    if (!blob) {
                        reject(new Error('압축 실패'));
                        return;
                    }
                    resolve(blob);
                }, mimeType);
                return;
            }

            // JPEG인 경우 품질 조정으로 목표 용량 달성 시도
            var minQuality = 0.1;
            var maxQuality = quality;
            var currentQuality = quality;
            var attempts = 0;
            var maxAttempts = 10;

            function tryCompress() {
                canvas.toBlob(function(blob) {
                    if (!blob) {
                        reject(new Error('압축 실패'));
                        return;
                    }

                    // 목표 용량 달성 또는 최소 품질 도달
                    if (blob.size <= targetSize || currentQuality <= minQuality || attempts >= maxAttempts) {
                        resolve(blob);
                        return;
                    }

                    // 이진 탐색으로 품질 조정
                    if (blob.size > targetSize) {
                        maxQuality = currentQuality;
                        currentQuality = (currentQuality + minQuality) / 2;
                    } else {
                        minQuality = currentQuality;
                        currentQuality = (currentQuality + maxQuality) / 2;
                    }

                    attempts++;
                    tryCompress();
                }, mimeType, currentQuality);
            }

            tryCompress();
        });
    }

    /**
     * 이미지 압축 및 리사이징
     * @param {File} file - 원본 파일
     * @param {Object} options - 옵션
     * @param {number} options.maxWidth - 최대 너비
     * @param {number} options.maxHeight - 최대 높이
     * @param {number} options.quality - 품질 (0-1, JPEG/WebP만 적용)
     * @param {number} options.targetSize - 목표 용량 (bytes, JPEG/WebP만 적용)
     * @param {boolean} options.useWebP - WebP 변환 사용 여부 (기본값: false, WebP 지원 시 자동 감지)
     * @returns {Promise<File>}
     */
    function compressImage(file, options) {
        return new Promise(function(resolve, reject) {
            if (!isImageFile(file)) {
                resolve(file); // 이미지가 아니면 원본 반환
                return;
            }

            var maxWidth = options.maxWidth || 1920;
            var maxHeight = options.maxHeight || 1920;
            var targetSize = options.targetSize || 150 * 1024; // 150KB
            var quality = options.quality || 0.80; // JPEG/WebP 기본 품질
            
            // WebP 변환 여부
            var useWebP = options.useWebP !== undefined ? options.useWebP : false;
            
            // 원본 파일의 MIME 타입과 확장자
            var originalMimeType = file.type;
            var originalFileName = file.name;
            
            // WebP 변환 여부에 따라 최종 MIME 타입과 파일명 결정
            var mimeType, fileName;
            if (useWebP && webPSupported) {
                mimeType = 'image/webp';
                // 파일명 확장자를 .webp로 변경
                if (originalFileName.lastIndexOf('.') > 0) {
                    fileName = originalFileName.substring(0, originalFileName.lastIndexOf('.')) + '.webp';
                } else {
                    fileName = originalFileName + '.webp';
                }
            } else {
                // 원본 포맷 유지
                mimeType = originalMimeType;
                fileName = originalFileName;
            }

            // 압축 필요 여부 확인
            shouldCompress(file).then(function(needsCompress) {
                if (!needsCompress) {
                    resolve(file); // 압축 불필요하면 원본 반환
                    return;
                }

                var img = new Image();
                var objectUrl = URL.createObjectURL(file);

                img.onload = function() {
                    try {
                        URL.revokeObjectURL(objectUrl);

                        // 리사이징 크기 계산
                        var dimensions = calculateDimensions(img, maxWidth, maxHeight);

                        // 리사이징이 필요없고 WebP 변환도 하지 않는 경우 원본 반환
                        if (dimensions.width === img.width && dimensions.height === img.height && !useWebP) {
                            // JPEG인 경우에만 품질 조정으로 압축 시도
                            var isJpeg = /^image\/(jpeg|jpg)$/i.test(originalMimeType);
                            if (!isJpeg) {
                                resolve(file);
                                return;
                            }
                        }

                        // Canvas 생성 및 이미지 그리기
                        var canvas = document.createElement('canvas');
                        canvas.width = dimensions.width;
                        canvas.height = dimensions.height;

                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, dimensions.width, dimensions.height);

                        // 목표 용량에 맞게 압축 (원본 MIME 타입 유지)
                        compressToTargetSize(canvas, mimeType, quality, targetSize).then(function(blob) {
                            // File 객체로 변환 (원본 파일명과 확장자 유지)
                            var compressedFile = new File([blob], fileName, {
                                type: mimeType,
                                lastModified: Date.now()
                            });

                            resolve(compressedFile);
                        }).catch(function(error) {
                            console.error('이미지 압축 실패:', error);
                            resolve(file); // 실패 시 원본 반환
                        });
                    } catch (error) {
                        console.error('이미지 처리 오류:', error);
                        resolve(file); // 오류 시 원본 반환
                    }
                };

                img.onerror = function() {
                    URL.revokeObjectURL(objectUrl);
                    console.error('이미지 로드 실패');
                    resolve(file); // 로드 실패 시 원본 반환
                };

                img.src = objectUrl;
            }).catch(function(error) {
                console.error('압축 필요 여부 확인 실패:', error);
                resolve(file); // 오류 시 원본 반환
            });
        });
    }

    // 전역으로 노출
    window.GdImageCompress = {
        compress: compressImage,
        isImageFile: isImageFile,
        shouldCompress: shouldCompress
    };

})();

