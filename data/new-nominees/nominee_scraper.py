import requests
from bs4 import BeautifulSoup
import csv
import re
import os
from datetime import datetime

# Get the directory where this script is located
script_dir = os.path.dirname(os.path.abspath(__file__))

# Input and output file names
input_file = os.path.join(script_dir, "urls.txt")
output_file = os.path.join(script_dir, "nominee-data.csv")

# Column headers for the CSV
headers = ["Name", "Slug", "Photo URL", "Wikipedia URL", "Birthday"]

def remove_parentheses_and_square_brackets(text):
    """Remove all content within parentheses, square brackets, and the brackets themselves."""
    text = re.sub(r'\(.*?\)', '', text)  # Remove content in parentheses
    text = re.sub(r'\[.*?\]', '', text)  # Remove content in square brackets
    return text.strip()

def clean_html(text):
    """Remove all HTML tags from the input text."""
    return re.sub(r'<[^>]+>', '', text).strip()

def extract_text(element):
    """Extract and clean text from a BeautifulSoup element."""
    if not element:
        return ""
    raw_content = element.decode_contents()
    cleaned_content = remove_parentheses_and_square_brackets(clean_html(raw_content))
    return cleaned_content.strip()

def fix_image_url(image_url):
    """Ensure the image URL starts with 'https:' if it begins with '//'."""
    if image_url and image_url.startswith("//"):
        return "https:" + image_url
    return image_url

def get_day_suffix(day):
    """Return the appropriate suffix for a given day (st, nd, rd, th)."""
    if 11 <= day <= 13:
        return "th"
    last_digit = day % 10
    if last_digit == 1:
        return "st"
    elif last_digit == 2:
        return "nd"
    elif last_digit == 3:
        return "rd"
    else:
        return "th"

def format_birthday(date_string):
    """Convert YYYY-MM-DD to '14th March, 1967' format."""
    try:
        date_obj = datetime.strptime(date_string, "%Y-%m-%d")
        day = date_obj.day
        month = date_obj.strftime("%B")
        year = date_obj.year
        return f"{day}{get_day_suffix(day)} {month}, {year}"
    except ValueError:
        return ""

def extract_birthday(soup):
    """Extract the person's birthday and format it correctly."""
    born_row = soup.find('th', string="Born")
    if born_row:
        birthday_data = born_row.find_next_sibling('td')
        if birthday_data:
            bday_span = birthday_data.find('span', class_='bday')
            if bday_span:
                return format_birthday(bday_span.text)  # Convert to required format
    return ""

def scrape_wikipedia_page(url):
    """Scrape data from a single Wikipedia biography page."""
    headers = {
        'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    }
    response = requests.get(url, headers=headers)
    soup = BeautifulSoup(response.text, 'html.parser')
    
    data = {}
    
    # Name (h1 tag)
    name_element = soup.find('h1', id='firstHeading')
    if name_element:
        # Get text and remove content in parentheses/brackets
        name_text = name_element.get_text(strip=True)
        name_text = re.sub(r'\(.*?\)', '', name_text)
        name_text = re.sub(r'\[.*?\]', '', name_text)
        data['Name'] = name_text.strip()
    else:
        data['Name'] = ""
    
    # Slug
    slug = url.split("/wiki/")[-1]
    data['Slug'] = slug.replace('_', '-')
    
    # Photo URL
    photo = soup.select_one('.infobox-image img')
    data['Photo URL'] = fix_image_url(photo['src']) if photo else ""
    
    # Wikipedia URL
    data['Wikipedia URL'] = url
    
    # Birthday (formatted correctly)
    data['Birthday'] = extract_birthday(soup)
    
    return data

def main():
    # Read URLs from input file
    with open(input_file, 'r') as file:
        urls = [line.strip() for line in file.readlines()]
    
    failed_urls = []
    
    # Open the CSV file for writing
    with open(output_file, 'w', newline='', encoding='utf-8') as file:
        writer = csv.DictWriter(file, fieldnames=headers)
        writer.writeheader()
        
        # Scrape each URL and write to CSV
        for url in urls:
            print(f"Scraping {url}...")
            try:
                person_data = scrape_wikipedia_page(url)
                writer.writerow(person_data)
                
                # Check if scraping failed (no Name extracted)
                if not person_data.get('Name'):
                    failed_urls.append(url)
                    print(f"  WARNING: No data found for {url}")
            except Exception as e:
                print(f"  ERROR: Failed to scrape {url}: {e}")
                failed_urls.append(url)
    
    # Report results
    if failed_urls:
        print(f"\n{len(failed_urls)} URLs failed to scrape properly")
    else:
        print("\nAll URLs scraped successfully!")

if __name__ == "__main__":
    main()