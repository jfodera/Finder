// Add these functions just before renderMatches() 
async function handleMatch(matchId, action) {
  try {
    const response = await fetch('handleMatch.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ match_id: matchId, action: action })
    });
    
    if (!response.ok) throw new Error('Network response was not ok');
    
    const result = await response.json();
    if (result.success) {
      // Refresh both matches and items views after action
      await Promise.all([renderMatches(), renderItems()]);
    } else {
      alert(result.message || 'Failed to process match');
    }
  } catch (error) {
    console.error('Error handling match:', error);
    alert('An error occurred. Please try again.');
  }
}

async function handleUserMatch(matchId, action) {
  try {
    const response = await fetch('handleUserMatch.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ match_id: matchId, action: action })
    });
    
    if (!response.ok) throw new Error('Network response was not ok');
    
    const result = await response.json();
    if (result.success) {
      // Refresh both matches and items views after action
      await Promise.all([renderMatches(), renderItems()]);
    } else {
      alert(result.message || 'Failed to process match');
    }
  } catch (error) {
    console.error('Error handling user match:', error);
    alert('An error occurred. Please try again.');
  }
}