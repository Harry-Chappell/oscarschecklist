import re
import os
import pandas as pd
from bs4 import BeautifulSoup

# Get the directory where this script is located
script_dir = os.path.dirname(os.path.abspath(__file__))

# Load the HTML content
with open(os.path.join(script_dir, 'page.html'), 'r', encoding='utf-8') as file:
    soup = BeautifulSoup(file, 'html.parser')

# Extract nominations data
nominations = []

current_year = 2025  # Update to the relevant year
unique_id_counter = {}

# Helper function to clean and replace text
def clean_text(text):
    # Replace unwanted phrases
    replacements = [
        ("from ", ""),
        ("Music and Lyric by ", ""),
        ("Production Design: ", ""),
        ("Set Decoration: ", "")
    ]
    for old, new in replacements:
        text = text.replace(old, new)
    # Replace " and " and " & " last
    text = text.replace(" and ", ",").replace(" & ", ",").replace(";", ",")
    return text.strip()

# Process each field__item
for field_item in soup.find_all('div', class_='field__item'):
    award_category = field_item.find('div', class_='field--name-field-award-category-oscars')
    if not award_category:
        continue

    category = award_category.text.strip()
    honorees = field_item.find_all('div', class_='field--name-field-award-honorees')

    for honoree in honorees:
        for nominee_item in honoree.find_all('div', class_='field__item'):
            honoree_type = nominee_item.find('div', class_='field--name-field-honoree-type')
            if not honoree_type:
                continue

            honoree_type_text = honoree_type.text.strip()
            film_div = nominee_item.find('div', class_='field--name-field-award-film')
            film = film_div.text.strip() if film_div else ""

            nominee_div = nominee_item.find('div', class_='field--name-field-award-entities')
            nominee = nominee_div.text.strip() if nominee_div else ""

            # Clean text
            nominee_cleaned = clean_text(nominee)

            # Remove the film name from the nominees field using the exact film value
            if film and film in nominee_cleaned:
                nominee_cleaned = nominee_cleaned.replace(film, "").strip(", ").strip()

            # Handle ID
            if category == "Music (Original Song)":
                # Film name is before ";" in the nominee field
                film_name_match = re.search(r'from ([^;]+)', nominee)
                film_name = film_name_match.group(1).strip() if film_name_match else ""
                song_name = film
                film = film_name
                ID = song_name
            else:
                # Generate a unique ID
                category_id = re.sub(r'[^\w\s-]', '', category).lower().replace(' ', '-')
                unique_id_counter[category_id] = unique_id_counter.get(category_id, 0) + 1
                ID = f"{category_id}-{current_year}-{unique_id_counter[category_id]}"

            nominations.append({
                "ID": ID,
                "Category": category,
                "Nominees": nominee_cleaned,
                "Film": film
            })

# Convert to DataFrame
df = pd.DataFrame(nominations)

# Save to CSV
df.to_csv(os.path.join(script_dir, 'nominations.csv'), index=False)
print("Nominations data has been processed and saved to 'nominations.csv'.")