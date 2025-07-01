#!/bin/bash

# Dernier tag version (ex: v1.2.3)
LAST_TAG=$(git describe --tags --abbrev=0)

if [ -z "$(git log "$LAST_TAG"..HEAD --oneline)" ]; then
  echo "🚫 Aucun nouveau commit depuis le dernier tag ($LAST_TAG). Le changelog ne sera pas mis à jour."
  exit 0
fi

# Date actuelle
DATE=$(date +%F)

# Supprimer le préfixe "v" si présent (ex: v1.2.3 → 1.2.3)
RAW_VERSION=$(sed 's/^v//' <<< "$LAST_TAG")
IFS='.' read -r MAJOR MINOR PATCH <<< "$RAW_VERSION"

# Flags pour déterminer le type de changement
HAS_BREAKING=false
HAS_FEAT=false
HAS_FIX=false

# Analyser les types de commit depuis le dernier tag
TMP_LOG="full_log.tmp"
# git log "$LAST_TAG"..HEAD --pretty=format:"%s%n%b" > "$TMP_LOG"
# git log "$LAST_TAG"..HEAD --pretty=format:"%H %s" | grep "#"
git log -1 --pretty=format:"%s%n%b" > "$TMP_LOG"

while IFS= read -r line; do
  echo "$line" | grep -qi "BREAKING CHANGE"
  if [ $? -eq 0 ]; then
    HAS_BREAKING=true
  else
    echo "$line" | grep -qE '^feat:|^add:'
    if [ $? -eq 0 ]; then
      HAS_FEAT=true
    else
      echo "$line" | grep -qE '^fix:'
      if [ $? -eq 0 ]; then
        HAS_FIX=true
      fi
    fi
  fi
done < "$TMP_LOG"

# Appliquer la logique de versioning sémantique
if $HAS_BREAKING; then
  ((MAJOR++))
  MINOR=0
  PATCH=0
elif $HAS_FEAT; then
  ((MINOR++))
  PATCH=0
elif $HAS_FIX; then
  ((PATCH++))
else
  echo "ℹ️ Aucun changement significatif détecté. Le changelog ne sera pas mis à jour."
  rm -f "$TMP_LOG"
  exit 0
fi

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"

if [ "v$NEW_VERSION" == "$LAST_TAG" ]; then
  echo "La version calculée ($NEW_VERSION) est identique au dernier tag. Aucun nouveau tag ne sera créé."
else
  git tag -f "v$NEW_VERSION"
fi

# Fichiers temporaires
CHANGELOG_TMP="new_changelog.md"
BREAKING_TMP="breaking_changes.tmp"
ADDED_TMP="added.tmp"
CHANGED_TMP="changed.tmp"
FIXED_TMP="fixed.tmp"
OTHER_TMP="other.tmp"

# Nettoyage fichiers temporaires
> "$CHANGELOG_TMP"
> "$BREAKING_TMP"
> "$ADDED_TMP"
> "$CHANGED_TMP"
> "$FIXED_TMP"
> "$OTHER_TMP"

# Traitement des commits
while IFS= read -r line; do
  echo "$line" | grep -qi "BREAKING CHANGE"
  if [ $? -eq 0 ]; then
    echo "- ❗ ${line}" >> "$BREAKING_TMP"
    continue
  fi

  echo "$line" | grep -qE '^feat:|^add:'
  if [ $? -eq 0 ]; then
    echo "- ${line}" >> "$ADDED_TMP"
    continue
  fi

  echo "$line" | grep -qE '^fix:'
  if [ $? -eq 0 ]; then
    echo "- ${line}" >> "$FIXED_TMP"
    continue
  fi

  echo "$line" | grep -qE '^change:|^refactor:|^update:'
  if [ $? -eq 0 ]; then
    echo "- ${line}" >> "$CHANGED_TMP"
    continue
  fi

  if [ -n "$line" ]; then
    echo "- ${line}" >> "$OTHER_TMP"
  fi
done < "$TMP_LOG"

# Écriture du header du changelog
echo "## [$NEW_VERSION] - $DATE" >> "$CHANGELOG_TMP"

# Section breaking changes si présente
if [ -s "$BREAKING_TMP" ]; then
  echo "" >> "$CHANGELOG_TMP"
  echo "### ⚠️ Breaking Changes" >> "$CHANGELOG_TMP"
  cat "$BREAKING_TMP" >> "$CHANGELOG_TMP"
fi

# Sections par type de commit
if [ -s "$ADDED_TMP" ]; then
  echo "" >> "$CHANGELOG_TMP"
  echo "### ✨ Added" >> "$CHANGELOG_TMP"
  cat "$ADDED_TMP" >> "$CHANGELOG_TMP"
fi

if [ -s "$CHANGED_TMP" ]; then
  echo "" >> "$CHANGELOG_TMP"
  echo "### 🔧 Changed" >> "$CHANGELOG_TMP"
  cat "$CHANGED_TMP" >> "$CHANGELOG_TMP"
fi

if [ -s "$FIXED_TMP" ]; then
  echo "" >> "$CHANGELOG_TMP"
  echo "### 🐛 Fixed" >> "$CHANGELOG_TMP"
  cat "$FIXED_TMP" >> "$CHANGELOG_TMP"
fi

if [ -s "$OTHER_TMP" ]; then
  echo "" >> "$CHANGELOG_TMP"
  echo "### 📦 Other" >> "$CHANGELOG_TMP"
  cat "$OTHER_TMP" >> "$CHANGELOG_TMP"
fi

# Ajout d’un saut de ligne
echo "" >> "$CHANGELOG_TMP"

# Fusion avec changelog existant
if [ -f CHANGELOG.md ]; then
  cat "$CHANGELOG_TMP" CHANGELOG.md > tmp && mv tmp CHANGELOG.md
else
  mv "$CHANGELOG_TMP" CHANGELOG.md
fi

# Nettoyage
rm -f "$BREAKING_TMP" "$ADDED_TMP" "$CHANGED_TMP" "$FIXED_TMP" "$OTHER_TMP" "$TMP_LOG"

# Affichage final
echo "✅ Changelog généré :"
cat CHANGELOG.md