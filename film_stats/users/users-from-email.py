import os
import re
import json
from datetime import datetime
from email import policy
from email.parser import BytesParser

# Define the emails directory
EMAILS_DIR = os.path.join(os.path.dirname(__file__), 'emails')
OUTPUT_FILE = os.path.join(os.path.dirname(__file__), 'signup_dates.json')

def parse_delivery_date(email_path):
    """
    Extract the Delivery-date from an email file.
    Returns the date in ISO 8601 format (Chart.js compatible) or None if not found.
    """
    try:
        with open(email_path, 'rb') as f:
            msg = BytesParser(policy=policy.default).parse(f)
            
        # Look for Delivery-date header
        delivery_date = msg.get('Delivery-date')
        
        if delivery_date:
            # Parse the date string
            # Format: "Wed, 21 Jan 2026 01:52:03 +0000"
            # Remove timezone for parsing, then format as ISO 8601
            date_match = re.match(r'[A-Za-z]+,\s+(.+?)\s+[\+\-]\d{4}', delivery_date)
            if date_match:
                date_str = date_match.group(1)
                dt = datetime.strptime(date_str, '%d %b %Y %H:%M:%S')
                # Return ISO 8601 format (YYYY-MM-DDTHH:MM:SS)
                return dt.strftime('%Y-%m-%dT%H:%M:%S')
                
    except Exception as e:
        print(f"Error processing {os.path.basename(email_path)}: {e}")
    
    return None

def main():
    """
    Process all .eml files in the emails directory and compile signup dates.
    """
    signup_data = []
    
    # Check if emails directory exists
    if not os.path.exists(EMAILS_DIR):
        print(f"Error: Emails directory not found at {EMAILS_DIR}")
        return
    
    # Get all .eml files
    email_files = [f for f in os.listdir(EMAILS_DIR) if f.endswith('.eml')]
    print(f"Found {len(email_files)} email files")
    
    # Process each email
    for email_file in email_files:
        email_path = os.path.join(EMAILS_DIR, email_file)
        delivery_date = parse_delivery_date(email_path)
        
        if delivery_date:
            signup_data.append({
                'date': delivery_date,
            })
    
    # Sort by date
    signup_data.sort(key=lambda x: x['date'])
    
    # Add running total to each signup
    for i, signup in enumerate(signup_data, start=1):
        signup['count'] = i
    
    # Create output object
    output = {
        'signups': signup_data,
        'total_count': len(signup_data),
        'generated_at': datetime.now().strftime('%Y-%m-%dT%H:%M:%S')
    }
    
    # Write to JSON file
    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(output, f, ensure_ascii=False)
    
    print(f"\nProcessed {len(signup_data)} signups")
    print(f"Output saved to: {OUTPUT_FILE}")
    
    # Show sample of data
    if signup_data:
        print(f"\nFirst signup: {signup_data[0]['date']}")
        print(f"Latest signup: {signup_data[-1]['date']}")

if __name__ == '__main__':
    main()
