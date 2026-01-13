import requests
from bs4 import BeautifulSoup
import csv
import re
from pathlib import Path

# Get the directory where this script is located
script_dir = Path(__file__).parent

# Input and output file names
input_file = script_dir / "film-urls.txt"
output_file = script_dir / "film-data.csv"
failed_urls_file = script_dir / "failed-urls.txt"

# Column headers for the CSV
headers = [
    "Name", "Slug", "Poster URL", "Running time", "Release Date",
    "Budget", "Box Office", "Production Companies", "Distribution Companies",
    "Country", "Language"
]

def remove_parentheses_and_square_brackets(text):
    """Remove all content within parentheses, square brackets, and the brackets themselves."""
    text = re.sub(r'\(.*?\)', '', text)  # Remove content in parentheses
    text = re.sub(r'\[.*?\]', '', text)  # Remove content in square brackets
    return text.strip()

def clean_html(text):
    """Remove all HTML tags from the input text."""
    return re.sub(r'<[^>]+>', '', text).strip()

def remove_html_with_separator(text):
    """Replace all HTML tags with | and remove excess separators."""
    text = re.sub(r'<[^>]+>', '|', text)  # Replace HTML tags with |
    return re.sub(r'\|+', '|', text).strip('|')  # Remove consecutive | and trim

def extract_text(element, use_separator=True):
    """Extract and clean text from a BeautifulSoup element."""
    if not element:
        return ""
    raw_content = element.decode_contents()
    # Specifically for the Name field, we remove parentheses and square brackets, then clean HTML
    if element.name == 'h1':  # We treat the title (h1) field differently
        cleaned_content = remove_parentheses_and_square_brackets(raw_content)
        cleaned_content = clean_html(cleaned_content)
    else:
        cleaned_content = remove_parentheses_and_square_brackets(raw_content)
        if use_separator:
            cleaned_content = remove_html_with_separator(cleaned_content)
        else:
            cleaned_content = clean_html(cleaned_content)
    return cleaned_content.strip()

def format_running_time(running_time):
    """Convert running time to h:mm format if possible."""
    match = re.search(r'(\d+)\s*min', running_time)  # Find minutes
    if match:
        minutes = int(match.group(1))
        hours, mins = divmod(minutes, 60)
        return f"{hours}:{mins:02d}"
    return running_time  # Return as-is if no match

def extract_first_year(text):
    """Extract everything up to and including the first 4-digit year."""
    match = re.search(r'(.*?\b\d{4}\b)', text)
    return match.group(1).strip() if match else text

def process_multivalue_field(element):
    """Extract and join multiple values from HTML elements."""
    if not element:
        return ""
    raw_content = element.decode_contents()
    cleaned_content = remove_parentheses_and_square_brackets(remove_html_with_separator(raw_content))
    values = [item.strip() for item in cleaned_content.split('|') if item.strip()]
    return " | ".join(values)

def fix_poster_url(poster_url):
    """Ensure the poster URL starts with 'https:' if it begins with '//'."""
    if poster_url.startswith("//"):
        return "https:" + poster_url
    return poster_url

def get_production_companies(music_row):
    """Find the production companies row that follows the 'Music by' row."""
    if not music_row:
        return ""
    next_row = music_row.find_next('tr')
    if next_row:
        th_text = extract_text(next_row.find('th'))
        if "Production" in th_text and "companies" in th_text:
            return process_multivalue_field(next_row.find('td'))
    return ""

def scrape_wikipedia_page(url):
    """Scrape data from a single Wikipedia film page."""
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
    data['Slug'] = url.split("/wiki/")[-1]
    
    # Poster URL
    poster = soup.select_one('.infobox-image img')
    data['Poster URL'] = fix_poster_url(poster['src']) if poster else ""
    
    # Running time
    running_time = soup.find('th', string="Running time")
    raw_running_time = extract_text(running_time.find_next_sibling('td')) if running_time else ""
    data['Running time'] = format_running_time(raw_running_time)
    
    # Release Date
    release_date = soup.find('th', string=["Release date", "Release dates"])
    raw_release_date = extract_text(release_date.find_next_sibling('td'), use_separator=False) if release_date else ""
    data['Release Date'] = extract_first_year(raw_release_date)
    
    # Budget
    budget = soup.find('th', string="Budget")
    data['Budget'] = extract_text(budget.find_next_sibling('td'), use_separator=False) if budget else ""
    
    # Box Office
    box_office = soup.find('th', string="Box office")
    data['Box Office'] = extract_text(box_office.find_next_sibling('td'), use_separator=False) if box_office else ""
    
    # Distribution Companies
    distribution_row = soup.find('th', string="Distributed by")
    distribution_data = distribution_row.find_next_sibling('td') if distribution_row else None
    data['Distribution Companies'] = process_multivalue_field(distribution_data)
    
    # Production Companies
    music_row = soup.find('th', string="Music by")
    data['Production Companies'] = get_production_companies(music_row)
    
    # Country
    country = soup.find('th', string="Country")
    data['Country'] = process_multivalue_field(country.find_next_sibling('td')) if country else ""
    
    # Language
    language = soup.find('th', string=["Language", "Languages"])
    data['Language'] = process_multivalue_field(language.find_next_sibling('td')) if language else ""
    
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
                film_data = scrape_wikipedia_page(url)
                writer.writerow(film_data)
                
                # Check if scraping failed (no Name extracted)
                if not film_data.get('Name'):
                    failed_urls.append(url)
                    print(f"  WARNING: No data found for {url}")
            except Exception as e:
                print(f"  ERROR: Failed to scrape {url}: {e}")
                failed_urls.append(url)
    
    # Write failed URLs to a separate file
    if failed_urls:
        with open(failed_urls_file, 'w') as file:
            for url in failed_urls:
                file.write(url + '\n')
        print(f"\n{len(failed_urls)} failed URLs saved to {failed_urls_file}")
    else:
        print("\nAll URLs scraped successfully!")

if __name__ == "__main__":
    main()