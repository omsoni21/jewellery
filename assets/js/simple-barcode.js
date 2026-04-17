/**
 * Simple Barcode Generator - No External Dependencies
 * Creates a visual barcode using pure HTML/CSS
 */

function generateSimpleBarcode(containerId, data) {
    var container = document.getElementById(containerId);
    if (!container) return;
    
    // Clear container
    container.innerHTML = '';
    
    // Create barcode using divs
    var barcodeDiv = document.createElement('div');
    barcodeDiv.style.fontFamily = 'Libre Barcode 128, monospace';
    barcodeDiv.style.fontSize = '60px';
    barcodeDiv.style.textAlign = 'center';
    barcodeDiv.style.padding = '20px';
    barcodeDiv.style.background = 'white';
    barcodeDiv.textContent = data;
    
    container.appendChild(barcodeDiv);
    
    // Add text below
    var textDiv = document.createElement('div');
    textDiv.style.marginTop = '10px';
    textDiv.style.fontFamily = 'monospace';
    textDiv.style.fontSize = '14px';
    textDiv.style.fontWeight = 'bold';
    textDiv.textContent = data;
    
    container.appendChild(textDiv);
}

// Alternative: Generate barcode using canvas
function generateCanvasBarcode(canvasId, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    var ctx = canvas.getContext('2d');
    var width = canvas.width;
    var height = canvas.height;
    
    // Clear canvas
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, width, height);
    
    // Simple barcode pattern based on data
    ctx.fillStyle = 'black';
    var x = 10;
    var barWidth = 2;
    
    // Generate pattern from data string
    for (var i = 0; i < data.length; i++) {
        var charCode = data.charCodeAt(i);
        var binary = charCode.toString(2);
        
        for (var j = 0; j < binary.length; j++) {
            if (binary[j] === '1') {
                ctx.fillRect(x, 10, barWidth, height - 40);
            }
            x += barWidth;
        }
        x += barWidth; // Space between characters
    }
    
    // Add text
    ctx.font = '14px monospace';
    ctx.textAlign = 'center';
    ctx.fillText(data, width / 2, height - 10);
}
