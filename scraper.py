# File: bs/scraper.py
# This script generates a diverse and realistic set of dummy product data in JSON format.
# It does NOT make any external API calls. Image URLs are placeholders that PHP will replace.

import json
import random
import sys
from urllib.parse import quote_plus

def generate_product_list():
    """Generates a list of realistic dummy product dictionaries."""
    
    all_products = []

    # --- GREATLY EXPANDED DATA TEMPLATES FOR MORE VARIETY ---
    product_templates = {
        'Laptops': ["UltraBook X1", "ProGamer Z9", "StudioBook Pro", "TravelLite 14"],
        'Smartphones': ["Galaxy S25 Ultra", "Pixel 10 Pro", "NovaPhone Max", "Titan 5G"],
        'Headphones': ["NoiseGuard Pro", "AirBeats Lite", "StudioPods Max", "Sport-Earbuds"],
        'T-Shirts': ["Classic Crew Neck", "V-Neck Basic Tee", "Graphic Print Tee", "Polo Shirt"],
        'Jeans': ["Slim Fit Denim", "Relaxed Straight Jeans", "Stretch-Fit Jeans", "Classic Bootcut"],
        'Sneakers': ["AirFlex Runner", "Classic Canvas High-Top", "StreetKing Retro", "TrailBlazer All-Terrain"],
        'Dresses': ["Summer Floral Dress", "Elegant Evening Gown", "Casual Sundress", "Boho Maxi Dress"],
        'Handbags': ["Leather Tote Bag", "Crossbody Messenger", "Elegant Clutch", "Travel Duffle"],
        'Coffee Makers': ["Espresso Pro Machine", "Drip-Filter Brewer", "Single-Serve Pod Maker", "French Press Classic"],
        'Cookware Sets': ["Non-Stick Ceramic Set", "Stainless Steel Professional", "Cast Iron Skillet Pack"],
        'Yoga Mats': ["Eco-Friendly Cork Mat", "Extra-Thick Comfort Mat", "Travel Lite Yoga Mat"],
        'Dumbbells': ["Adjustable Dumbbell Set", "Neoprene Coated Weights", "Hex Rubber Dumbbell"],
        'Skincare Serums': ["Vitamin C Brightening", "Hyaluronic Acid Hydration", "Retinol Night Repair"],
        'Action Figures': ["Super-Soldier Collectible", "Galaxy Knight Deluxe", "Mythic Hero Figurine"],
    }
    
    brand_data = {
        'Laptops': ['Dell', 'HP', 'Asus', 'Acer'],
        'Smartphones': ['Samsung', 'Google', 'OnePlus', 'Xiomi'],
        'Headphones': ['Sony', 'Bose', 'Sennheiser', 'JBL'],
        'T-Shirts': ['Nike', 'Adidas', 'Puma', 'Under Armour'],
        'Jeans': ['Levi\'s', 'Wrangler', 'Diesel', 'G-Star'],
        'Sneakers': ['Nike', 'Adidas', 'New Balance', 'Converse'],
        'Dresses': ['Zara', 'H&M', 'MANGO', 'Vero Moda'],
        'Handbags': ['Michael Kors', 'Coach', 'Kate Spade'],
        'Coffee Makers': ['Breville', 'De\'Longhi', 'Nespresso', 'Keurig'],
        'Cookware Sets': ['T-fal', 'Cuisinart', 'Calphalon'],
        'Yoga Mats': ['Liforme', 'Manduka', 'Gaiam'],
        'Dumbbells': ['Bowflex', 'CAP Barbell', 'AmazonBasics'],
        'Skincare Serums': ['The Ordinary', 'CeraVe', 'La Roche-Posay'],
        'Action Figures': ['Hasbro', 'Mattel', 'Funko'],
    }

    categories_to_generate = {
        "Electronics": ["Laptops", "Smartphones", "Headphones"],
        "Men's Fashion": ["T-Shirts", "Jeans", "Sneakers"],
        "Women's Fashion": ["Dresses", "Handbags", "Sneakers"],
        "Home & Kitchen": ["Coffee Makers", "Cookware Sets"],
        "Sports & Outdoors": ["Yoga Mats", "Dumbbells"],
        "Health & Beauty": ["Skincare Serums"],
        "Toys & Games": ["Action Figures"]
    }

    # --- Generation Loop ---
    for main_cat, sub_cats in categories_to_generate.items():
        for sub_cat in sub_cats:
            for _ in range(random.randint(2, 3)): # Generate 2-3 products per sub-category
                base_name = random.choice(product_templates.get(sub_cat, [f"{sub_cat} Item"]))
                brand_name = random.choice(brand_data.get(sub_cat, ['Generic Brand']))
                
                safe_category_text = quote_plus(f"{brand_name} {sub_cat}")
                gallery = [
                    f"https://via.placeholder.com/800x800.png?text={safe_category_text}+1",
                    f"https://via.placeholder.com/800x800.png?text={safe_category_text}+2",
                    f"https://via.placeholder.com/800x800.png?text={safe_category_text}+3"
                ]

                product = {
                    "title": f"{brand_name} {base_name} - {random.choice(['2024 Model', 'Pro Series', 'Gen II'])}",
                    "description": f"Discover the high-quality {base_name} from {brand_name}. Engineered for performance and designed for style. Perfect for both professionals and enthusiasts.",
                    "price": round(random.uniform(19.99, 2299.99), 2),
                    "rating": round(random.uniform(4.0, 5.0), 1),
                    "reviews_count": random.randint(50, 15000),
                    "discount_percentage": random.choice([0, 0, 0, 10, 15, 20, 25, 30, 45]),
                    "platform": "AI-Curated",
                    "main_category": main_cat,
                    "sub_category": sub_cat,
                    "brand": brand_name,
                    "image_url": gallery[0],
                    "gallery_images": gallery
                }
                all_products.append(product)
                
    random.shuffle(all_products)
    return all_products

if __name__ == "__main__":
    try:
        product_list = generate_product_list()
        print(json.dumps(product_list, indent=2))
    except Exception as e:
        print(f"Error in scraper.py: {str(e)}", file=sys.stderr)
        sys.exit(1)