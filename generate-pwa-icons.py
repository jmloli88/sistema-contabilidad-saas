#!/usr/bin/env python3
"""
Script para generar iconos PWA en diferentes tamaños
Requiere: pip install Pillow
"""

from PIL import Image, ImageDraw, ImageFont
import os

# Crear directorio para iconos si no existe
os.makedirs('public/images/icons', exist_ok=True)

# Tamaños de iconos necesarios para PWA
sizes = [72, 96, 128, 144, 152, 192, 384, 512]

# Colores del tema
bg_color = '#4f46e5'  # Indigo
text_color = '#ffffff'  # Blanco

def create_icon(size):
    """Crea un icono simple con las iniciales CM (ContaMed)"""
    # Crear imagen con fondo de color
    img = Image.new('RGB', (size, size), bg_color)
    draw = ImageDraw.Draw(img)
    
    # Calcular tamaño de fuente proporcional
    font_size = int(size * 0.4)
    
    try:
        # Intentar usar una fuente del sistema
        font = ImageFont.truetype("arial.ttf", font_size)
    except:
        # Si no está disponible, usar fuente por defecto
        font = ImageFont.load_default()
    
    # Texto a dibujar
    text = "CM"
    
    # Obtener dimensiones del texto
    bbox = draw.textbbox((0, 0), text, font=font)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]
    
    # Calcular posición centrada
    x = (size - text_width) / 2
    y = (size - text_height) / 2
    
    # Dibujar texto
    draw.text((x, y), text, fill=text_color, font=font)
    
    # Guardar imagen
    filename = f'public/images/icons/icon-{size}x{size}.png'
    img.save(filename, 'PNG')
    print(f'✓ Creado: {filename}')

# Generar todos los iconos
print('Generando iconos PWA...')
for size in sizes:
    create_icon(size)

print('\n✓ Todos los iconos han sido generados exitosamente!')
print('\nPara personalizar los iconos:')
print('1. Crea tu propio logo en formato PNG')
print('2. Usa una herramienta online como https://realfavicongenerator.net/')
print('3. Reemplaza los archivos en public/images/icons/')
