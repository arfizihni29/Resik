<?php
/**
 * CorrectionManager Class
 * 
 * Manages AI correction images for retraining purposes
 * Automatically copies corrected images to organized folders
 */
class CorrectionManager {
    private $baseDir;
    
    public function __construct() {

        $this->baseDir = dirname(__DIR__) . '/uploads/corrections/';
    }
    
    /**
     * Copy corrected image to appropriate folder
     * 
     * @param string $originalImagePath - Path to original image
     * @param string $aiPrediction - AI's prediction (organik/anorganik/b3)
     * @param string $userCorrection - User's correction (organik/anorganik/b3)
     * @param int $reportId - Report ID for filename
     * @return bool - Success status
     */
    public function saveCorrectedImage($originalImagePath, $aiPrediction, $userCorrection, $reportId) {

        if ($aiPrediction === $userCorrection) {
            return false;
        }
        

        $targetDir = $this->baseDir . "from_{$aiPrediction}/to_{$userCorrection}/";
        

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        

        $pathInfo = pathinfo($originalImagePath);
        $extension = isset($pathInfo['extension']) ? $pathInfo['extension'] : 'jpg';
        

        $timestamp = date('Ymd_His');
        $newFilename = "{$reportId}_{$timestamp}.{$extension}";
        $targetPath = $targetDir . $newFilename;
        

        if (file_exists($originalImagePath)) {
            if (copy($originalImagePath, $targetPath)) {

                $this->logCorrection($reportId, $aiPrediction, $userCorrection, $newFilename);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log correction to a text file for reference
     */
    private function logCorrection($reportId, $aiPrediction, $userCorrection, $filename) {
        $logFile = $this->baseDir . 'corrections_log.txt';
        $logEntry = sprintf(
            "[%s] Report #%d | AI: %s → User: %s | File: %s\n",
            date('Y-m-d H:i:s'),
            $reportId,
            strtoupper($aiPrediction),
            strtoupper($userCorrection),
            $filename
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Get statistics of corrections per category
     */
    public function getStatistics() {
        $stats = [];
        $categories = ['organik', 'anorganik', 'b3'];
        
        foreach ($categories as $from) {
            foreach ($categories as $to) {
                if ($from !== $to) {
                    $dir = $this->baseDir . "from_{$from}/to_{$to}/";
                    if (is_dir($dir)) {
                        $files = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                        $stats["from_{$from}_to_{$to}"] = count($files);
                    } else {
                        $stats["from_{$from}_to_{$to}"] = 0;
                    }
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get all correction images from a specific category
     */
    public function getCorrectionImages($aiPrediction, $userCorrection) {
        $dir = $this->baseDir . "from_{$aiPrediction}/to_{$userCorrection}/";
        
        if (!is_dir($dir)) {
            return [];
        }
        
        $images = [];
        $files = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $images[] = [
                'filename' => basename($file),
                'path' => $file,
                'url' => str_replace(dirname(__DIR__), '', $file),
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }
        

        usort($images, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $images;
    }
    
    /**
     * Count total corrections
     */
    public function getTotalCorrections() {
        $total = 0;
        $stats = $this->getStatistics();
        
        foreach ($stats as $count) {
            $total += $count;
        }
        
        return $total;
    }
    
    /**
     * Get correction path for a specific combination
     */
    public function getCorrectionPath($aiPrediction, $userCorrection) {
        return "uploads/corrections/from_{$aiPrediction}/to_{$userCorrection}/";
    }
    
    /**
     * Clean old corrections (optional - for maintenance)
     * Delete corrections older than X days
     */
    public function cleanOldCorrections($daysOld = 365) {
        $categories = ['organik', 'anorganik', 'b3'];
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $deletedCount = 0;
        
        foreach ($categories as $from) {
            foreach ($categories as $to) {
                if ($from !== $to) {
                    $dir = $this->baseDir . "from_{$from}/to_{$to}/";
                    if (is_dir($dir)) {
                        $files = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                        
                        foreach ($files as $file) {
                            if (filemtime($file) < $cutoffTime) {
                                if (unlink($file)) {
                                    $deletedCount++;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $deletedCount;
    }
}
?>













